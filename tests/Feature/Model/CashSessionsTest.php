<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cash register session and denominations JSON array casting', function () {
    $user = User::create([
        'name' => 'Cashier Karim',
        'email' => 'karim@counter.com',
        'password' => bcrypt('secret'),
        'role' => 'counter_boy',
    ]);

    $denominations = [
        '1000' => 15,
        '500' => 8,
        '100' => 20,
    ];

    $session = CashRegisterSession::create([
        'opened_by' => $user->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
        'denominations' => $denominations, // test json cast
    ]);

    expect($session->creator->name)->toBe('Cashier Karim')
        ->and($session->denominations)->toBeArray()
        ->and($session->denominations['1000'])->toBe(15)
        ->and($session->denominations['500'])->toBe(8)
        ->and($session->opening_cash)->toEqual(5000.00);
});

test('cashbook entries payment method enum casting', function () {
    $user = User::create([
        'name' => 'Cashier Karim',
        'email' => 'karim@counter.com',
        'password' => bcrypt('secret'),
        'role' => 'counter_boy',
    ]);

    $session = CashRegisterSession::create([
        'opened_by' => $user->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
    ]);

    $entry = CashbookEntry::create([
        'cash_register_session_id' => $session->id,
        'entry_type' => 'Sale',
        'direction' => TransactionDirection::In,
        'payment_method' => PaymentMethod::Bkash,
        'amount' => 13500.00,
        'notes' => 'Received from E-Bike sale',
        'created_by' => $user->id,
    ]);

    expect($entry->payment_method)->toBe(PaymentMethod::Bkash)
        ->and($entry->amount)->toEqual(13500.00)
        ->and($entry->direction)->toBe(TransactionDirection::In);
});
