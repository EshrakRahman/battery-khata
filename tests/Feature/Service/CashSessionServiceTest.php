<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Exceptions\ActiveCashSessionExistsException;
use App\Exceptions\InvalidDenominationsTotalException;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Cashier Rahim',
        'email' => 'rahim.cash@volt.com',
        'password' => bcrypt('secret'),
        'role' => 'counter_boy',
    ]);

    $this->service = new CashSessionService;
});

test('opening a cash session creates a new session record', function () {
    $session = $this->service->openSession(
        user: $this->user,
        openingCash: 5000.00,
        notes: 'Morning shift opening'
    );

    expect($session)->toBeInstanceOf(CashRegisterSession::class)
        ->and($session->opened_by)->toBe($this->user->id)
        ->and($session->opening_cash)->toEqual(5000.00)
        ->and($session->closed_at)->toBeNull()
        ->and($session->expected_cash)->toEqual(5000.00); // starts with opening cash

    $this->assertDatabaseHas('cash_register_sessions', [
        'id' => $session->id,
        'opened_by' => $this->user->id,
        'opening_cash' => 5000.00,
    ]);
});

test('cannot open a cash session if the user already has an active open session', function () {
    $this->service->openSession($this->user, 5000.00);

    $this->expectException(ActiveCashSessionExistsException::class);
    $this->expectExceptionMessage("User {$this->user->name} already has an active cash register session.");

    $this->service->openSession($this->user, 3000.00);
});

test('closing a cash session calculates expected cash filtering non-cash payments and calculates shortage or excess', function () {
    $session = $this->service->openSession($this->user, 5000.00);

    // 1. Record Cash Sales (In flow, Cash)
    CashbookEntry::create([
        'cash_register_session_id' => $session->id,
        'entry_type' => 'Sale',
        'direction' => TransactionDirection::In,
        'payment_method' => PaymentMethod::Cash,
        'amount' => 12000.00,
        'created_by' => $this->user->id,
    ]);

    // 2. Record bKash Sales (In flow, Non-Cash) - should be ignored in Expected cash
    CashbookEntry::create([
        'cash_register_session_id' => $session->id,
        'entry_type' => 'Sale',
        'direction' => TransactionDirection::In,
        'payment_method' => PaymentMethod::Bkash,
        'amount' => 15000.00,
        'created_by' => $this->user->id,
    ]);

    // 3. Record Expense Cash Out (Out flow, Cash)
    CashbookEntry::create([
        'cash_register_session_id' => $session->id,
        'entry_type' => 'Expense',
        'direction' => TransactionDirection::Out,
        'payment_method' => PaymentMethod::Cash,
        'amount' => 1500.00,
        'created_by' => $this->user->id,
    ]);

    // 4. Record Supplier Payment Cash Out (Out flow, Cash)
    CashbookEntry::create([
        'cash_register_session_id' => $session->id,
        'entry_type' => 'SupplierPayment',
        'direction' => TransactionDirection::Out,
        'payment_method' => PaymentMethod::Cash,
        'amount' => 3500.00,
        'created_by' => $this->user->id,
    ]);

    // Calculation:
    // Opening Cash: 5,000.00
    // + Cash In: 12,000.00
    // - Cash Out (Expense): 1,500.00
    // - Cash Out (Supplier): 3,500.00
    // Expected Cash: 5,000.00 + 12,000.00 - 1,500.00 - 3,500.00 = 12,000.00

    // Provide closing cash of 11,950.00 (shortage of 50.00)
    // Denominations: 1000 x 11 = 11000, 500 x 1 = 500, 100 x 4 = 400, 50 x 1 = 50. Total = 11950.00
    $denominations = [
        '1000' => 11,
        '500' => 1,
        '100' => 4,
        '50' => 1,
    ];

    $closedSession = $this->service->closeSession(
        session: $session,
        closingCash: 11950.00,
        denominations: $denominations,
        notes: 'Slight shortage in drawer cashier register closing note'
    );

    expect($closedSession->expected_cash)->toEqual(12000.00)
        ->and($closedSession->closing_cash)->toEqual(11950.00)
        ->and($closedSession->shortage_excess)->toEqual(-50.00)
        ->and($closedSession->closed_at)->not->toBeNull()
        ->and($closedSession->denominations)->toBe($denominations);

    $this->assertDatabaseHas('cash_register_sessions', [
        'id' => $session->id,
        'expected_cash' => 12000.00,
        'closing_cash' => 11950.00,
        'shortage_excess' => -50.00,
    ]);
});

test('closing a session fails if the sum of denominations does not match the closing cash amount', function () {
    $session = $this->service->openSession($this->user, 5000.00);

    // closing_cash is 5000.00, but denominations count adds up to 4800.00
    $denominations = [
        '1000' => 4,
        '500' => 1,
        '100' => 3,
    ];

    $this->expectException(InvalidDenominationsTotalException::class);
    $this->expectExceptionMessage('The sum of note denominations (4800.00) does not match the declared closing cash (5000.00).');

    $this->service->closeSession(
        session: $session,
        closingCash: 5000.00,
        denominations: $denominations
    );
});
