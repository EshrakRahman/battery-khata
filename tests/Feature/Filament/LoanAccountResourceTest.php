<?php

use App\Enums\LenderType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\LoanAccounts\Pages\CreateLoanAccount;
use App\Filament\Resources\LoanAccounts\Pages\EditLoanAccount;
use App\Filament\Resources\LoanAccounts\RelationManagers\TransactionsRelationManager;
use App\Models\CashbookEntry;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Rahim Supplier',
        'mobile' => '01711111111',
    ]);

    $this->customer = Customer::create([
        'name' => 'Karim Customer',
        'mobile' => '01811111111',
    ]);

    $this->actingAs($this->admin);
});

test('lender reference options are dynamically filtered based on lender type', function () {
    $component = Livewire::test(CreateLoanAccount::class);

    // 1. Set type to Supplier
    $component->set('data.lender_type', LenderType::Supplier->value);
    $options = $component->instance()->form->getComponent('lender_reference_id')->getOptions();
    expect($options)->toContain('Rahim Supplier')
        ->and($options)->not->toContain('Karim Customer');

    // 2. Set type to Customer
    $component->set('data.lender_type', LenderType::Customer->value);
    $options = $component->instance()->form->getComponent('lender_reference_id')->getOptions();
    expect($options)->toContain('Karim Customer')
        ->and($options)->not->toContain('Rahim Supplier');
});

test('creating a loan account sets outstanding balance to principal amount', function () {
    Livewire::test(CreateLoanAccount::class)
        ->fillForm([
            'loan_name' => 'Bank Loan Rahim',
            'lender_type' => LenderType::Supplier->value,
            'lender_reference_id' => $this->supplier->id,
            'principal_amount' => 50000.00,
            'outstanding_balance' => 50000.00,
            'start_date' => today()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $loan = LoanAccount::first();
    expect($loan)->not->toBeNull()
        ->and($loan->loan_name)->toBe('Bank Loan Rahim')
        ->and($loan->principal_amount)->toEqual('50000.00')
        ->and($loan->outstanding_balance)->toEqual('50000.00');
});

test('can log disbursement and increment parent outstanding balance and log cashbook entry', function () {
    // Open cash session
    $session = app(CashSessionService::class)->openSession($this->admin, 1000.00);

    // Create loan
    $loan = LoanAccount::create([
        'loan_name' => 'Bank Loan Rahim',
        'lender_type' => LenderType::Supplier,
        'lender_reference_id' => $this->supplier->id,
        'principal_amount' => 50000.00,
        'outstanding_balance' => 50000.00,
        'start_date' => today(),
    ]);

    // Add disbursement transaction
    Livewire::test(TransactionsRelationManager::class, [
        'ownerRecord' => $loan,
        'pageClass' => EditLoanAccount::class,
    ])
        ->callTableAction('create', data: [
            'transaction_date' => today()->toDateString(),
            'transaction_type' => 'Disbursement',
            'amount' => 10000.00,
            'payment_method' => PaymentMethod::Cash->value,
            'notes' => 'Received loan money',
        ])
        ->assertHasNoTableActionErrors();

    // Verify transaction
    $transaction = LoanTransaction::first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->amount)->toEqual('10000.00')
        ->and($transaction->transaction_type)->toBe('Disbursement');

    // Parent outstanding balance should increment to 60000.00
    expect($loan->refresh()->outstanding_balance)->toEqual('60000.00');

    // Verify CashbookEntry
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $session->id,
        'entry_type' => 'LoanReceive',
        'direction' => TransactionDirection::In->value,
        'payment_method' => PaymentMethod::Cash->value,
        'amount' => 10000.00,
        'reference_type' => LoanTransaction::class,
        'reference_id' => $transaction->id,
    ]);
});

test('can log repayment and decrement parent outstanding balance and log cashbook entry', function () {
    // Open cash session
    $session = app(CashSessionService::class)->openSession($this->admin, 1000.00);

    // Create loan
    $loan = LoanAccount::create([
        'loan_name' => 'Bank Loan Rahim',
        'lender_type' => LenderType::Supplier,
        'lender_reference_id' => $this->supplier->id,
        'principal_amount' => 50000.00,
        'outstanding_balance' => 50000.00,
        'start_date' => today(),
    ]);

    // Add repayment transaction
    Livewire::test(TransactionsRelationManager::class, [
        'ownerRecord' => $loan,
        'pageClass' => EditLoanAccount::class,
    ])
        ->callTableAction('create', data: [
            'transaction_date' => today()->toDateString(),
            'transaction_type' => 'Repayment',
            'amount' => 5000.00,
            'payment_method' => PaymentMethod::Cash->value,
            'notes' => 'Paid back loan money',
        ])
        ->assertHasNoTableActionErrors();

    // Verify transaction
    $transaction = LoanTransaction::first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->amount)->toEqual('5000.00')
        ->and($transaction->transaction_type)->toBe('Repayment');

    // Parent outstanding balance should decrement to 45000.00
    expect($loan->refresh()->outstanding_balance)->toEqual('45000.00');

    // Verify CashbookEntry
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $session->id,
        'entry_type' => 'LoanRepayment',
        'direction' => TransactionDirection::Out->value,
        'payment_method' => PaymentMethod::Cash->value,
        'amount' => 5000.00,
        'reference_type' => LoanTransaction::class,
        'reference_id' => $transaction->id,
    ]);
});

test('cannot log transaction without active cash session', function () {
    // Create loan
    $loan = LoanAccount::create([
        'loan_name' => 'Bank Loan Rahim',
        'lender_type' => LenderType::Supplier,
        'lender_reference_id' => $this->supplier->id,
        'principal_amount' => 50000.00,
        'outstanding_balance' => 50000.00,
        'start_date' => today(),
    ]);

    // Add transaction - should fail/halt because no cash session is open
    Livewire::test(TransactionsRelationManager::class, [
        'ownerRecord' => $loan,
        'pageClass' => EditLoanAccount::class,
    ])
        ->callTableAction('create', data: [
            'transaction_date' => today()->toDateString(),
            'transaction_type' => 'Repayment',
            'amount' => 5000.00,
            'payment_method' => PaymentMethod::Cash->value,
        ]);

    // Verify no transaction recorded
    expect(LoanTransaction::count())->toBe(0);
});

test('deleting transaction reverts parent balance and deletes cashbook entry', function () {
    // Open cash session
    $session = app(CashSessionService::class)->openSession($this->admin, 1000.00);

    // Create loan
    $loan = LoanAccount::create([
        'loan_name' => 'Bank Loan Rahim',
        'lender_type' => LenderType::Supplier,
        'lender_reference_id' => $this->supplier->id,
        'principal_amount' => 50000.00,
        'outstanding_balance' => 50000.00,
        'start_date' => today(),
    ]);

    // Create a transaction directly (triggers booted created hook)
    $transaction = new LoanTransaction([
        'loan_account_id' => $loan->id,
        'transaction_date' => today(),
        'transaction_type' => 'Repayment',
        'amount' => 10000.00,
        'notes' => 'Some repayment',
    ]);
    $transaction->payment_method = PaymentMethod::Cash;
    $transaction->save();

    expect($loan->refresh()->outstanding_balance)->toEqual('40000.00');

    $cashbook = CashbookEntry::where('reference_type', LoanTransaction::class)
        ->where('reference_id', $transaction->id)
        ->first();
    expect($cashbook)->not->toBeNull();

    // Delete transaction
    $transaction->delete();

    // Verify parent outstanding balance is reverted back to 50000.00
    expect($loan->refresh()->outstanding_balance)->toEqual('50000.00');

    // Verify CashbookEntry is deleted
    $this->assertDatabaseMissing('cashbook_entries', ['id' => $cashbook->id]);
});
