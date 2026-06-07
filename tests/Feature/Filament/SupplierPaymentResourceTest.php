<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\SupplierPayments\Pages\CreateSupplierPayment;
use App\Filament\Resources\SupplierPayments\Pages\ListSupplierPayments;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
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

    $this->counterBoy = User::create([
        'name' => 'Counter Boy',
        'email' => 'counter@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::CounterBoy,
        'is_active' => true,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Hamko Batteries',
        'mobile' => '01999888777',
        'supplier_type' => 'Manufacturer',
    ]);

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
        'expected_cash' => 5000.00,
    ]);
});

test('admin can list supplier payments', function () {
    $this->actingAs($this->admin);

    $payment = SupplierPayment::create([
        'supplier_id' => $this->supplier->id,
        'cash_register_session_id' => $this->session->id,
        'payment_date' => now(),
        'payment_method' => PaymentMethod::Cash,
        'amount' => 1500.00,
        'reference_no' => 'REF-001',
        'notes' => 'Bulk supply payment',
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(ListSupplierPayments::class)
        ->assertCanSeeTableRecords([$payment])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('supplier.name')
        ->assertCanRenderTableColumn('payment_date')
        ->assertCanRenderTableColumn('payment_method')
        ->assertCanRenderTableColumn('amount');
});

test('admin can log supplier payment with active session which updates ledger and cashbook', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateSupplierPayment::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::Cash->value,
            'amount' => 2000.00,
            'reference_no' => 'PAY-1002',
            'notes' => 'Weekly payment',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('supplier_payments', [
        'supplier_id' => $this->supplier->id,
        'amount' => 2000.00,
        'reference_no' => 'PAY-1002',
        'created_by' => $this->admin->id,
    ]);

    $payment = SupplierPayment::where('amount', 2000.00)->first();

    // Verify CashbookEntry is created
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $this->session->id,
        'entry_type' => 'SupplierPayment',
        'direction' => TransactionDirection::Out->value,
        'payment_method' => PaymentMethod::Cash->value,
        'amount' => 2000.00,
        'reference_type' => SupplierPayment::class,
        'reference_id' => $payment->id,
    ]);

    // Verify SupplierLedger is updated (payout = debit, decreases running balance)
    $this->assertDatabaseHas('supplier_ledgers', [
        'supplier_id' => $this->supplier->id,
        'transaction_type' => 'Payment',
        'debit' => 2000.00,
        'credit' => 0.00,
        'running_balance' => -2000.00, // starting at 0, debit reduces payable balance for suppliers
        'reference_type' => SupplierPayment::class,
        'reference_id' => $payment->id,
    ]);
});

test('counter boy cannot load create supplier payment page', function () {
    $this->actingAs($this->counterBoy);

    Livewire::test(CreateSupplierPayment::class)
        ->assertForbidden();
});

test('admin cannot load create supplier payment page without active register session', function () {
    // Delete the active session for admin
    $this->session->delete();

    $this->actingAs($this->admin);

    Livewire::test(CreateSupplierPayment::class)
        ->assertRedirect();
});
