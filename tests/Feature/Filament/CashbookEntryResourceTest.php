<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\CashbookEntries\Pages\ListCashbookEntries;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\User;
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

    $this->actingAs($this->admin);

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
        'expected_cash' => 1000.00,
    ]);
});

test('can list cashbook entries', function () {
    $entry = CashbookEntry::create([
        'cash_register_session_id' => $this->session->id,
        'entry_type' => 'Sale',
        'direction' => TransactionDirection::In,
        'payment_method' => PaymentMethod::Cash,
        'amount' => 1500.00,
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(ListCashbookEntries::class)
        ->assertCanSeeTableRecords([$entry])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('session.id')
        ->assertCanRenderTableColumn('entry_type')
        ->assertCanRenderTableColumn('direction')
        ->assertCanRenderTableColumn('payment_method')
        ->assertCanRenderTableColumn('amount')
        ->assertCanRenderTableColumn('creator.name')
        ->assertCanRenderTableColumn('created_at');
});
