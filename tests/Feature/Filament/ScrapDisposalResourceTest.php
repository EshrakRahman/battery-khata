<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\ScrapDisposals\Pages\CreateScrapDisposal;
use App\Filament\Resources\ScrapDisposals\Pages\ListScrapDisposals;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\ScrapCollection;
use App\Models\ScrapDisposal;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use App\Models\Warehouse;
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

    $this->supplier = Supplier::create([
        'name' => 'Hamko Batteries',
        'mobile' => '01999888777',
        'supplier_type' => 'Manufacturer',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Main Warehouse',
    ]);

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
        'expected_cash' => 5000.00,
    ]);

    // Create some InWarehouse scrap core items
    $this->scrap1 = ScrapCollection::create([
        'warehouse_id' => $this->warehouse->id,
        'scrap_type' => 'IPS Battery',
        'quantity' => 1,
        'unit_value' => 1000.00,
        'total_value' => 1000.00,
        'status' => 'InWarehouse',
        'created_by' => $this->admin->id,
    ]);

    $this->scrap2 = ScrapCollection::create([
        'warehouse_id' => $this->warehouse->id,
        'scrap_type' => 'Car Battery',
        'quantity' => 1,
        'unit_value' => 1200.00,
        'total_value' => 1200.00,
        'status' => 'InWarehouse',
        'created_by' => $this->admin->id,
    ]);
});

test('admin can list scrap disposals', function () {
    $disposal = ScrapDisposal::create([
        'supplier_id' => $this->supplier->id,
        'disposal_date' => now(),
        'payment_method' => PaymentMethod::Cash,
        'total_received' => 2200.00,
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(ListScrapDisposals::class)
        ->assertCanSeeTableRecords([$disposal])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('supplier.name')
        ->assertCanRenderTableColumn('disposal_date')
        ->assertCanRenderTableColumn('payment_method')
        ->assertCanRenderTableColumn('total_received');
});

test('admin can dispose scrap for cash with active register session', function () {
    Livewire::test(CreateScrapDisposal::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'disposal_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::Cash->value,
            'total_received' => 2200.00,
            'collections' => [$this->scrap1->id, $this->scrap2->id],
            'notes' => 'Cash disposal to factory agent',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify ScrapDisposal is created
    $this->assertDatabaseHas('scrap_disposals', [
        'total_received' => 2200.00,
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    $disposal = ScrapDisposal::where('total_received', 2200.00)->first();

    // Verify scrap collection statuses are changed to Disposed
    $this->scrap1->refresh();
    $this->scrap2->refresh();
    expect($this->scrap1->status)->toBe('Disposed')
        ->and($this->scrap1->scrap_disposal_id)->toBe($disposal->id)
        ->and($this->scrap2->status)->toBe('Disposed')
        ->and($this->scrap2->scrap_disposal_id)->toBe($disposal->id);

    // Verify CashbookEntry is created (direction In, cash register session, amount 2200.00)
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $this->session->id,
        'entry_type' => 'ScrapDisposal',
        'direction' => TransactionDirection::In->value,
        'payment_method' => PaymentMethod::Cash->value,
        'amount' => 2200.00,
        'reference_type' => ScrapDisposal::class,
        'reference_id' => $disposal->id,
    ]);
});

test('admin can dispose scrap for ledger offset without active register session', function () {
    // Delete active register session so we test it works without one
    $this->session->delete();

    Livewire::test(CreateScrapDisposal::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'disposal_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::ScrapAdjustment->value,
            'total_received' => 2200.00,
            'collections' => [$this->scrap1->id, $this->scrap2->id],
            'notes' => 'Ledger credit adjustment',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify ScrapDisposal is created
    $this->assertDatabaseHas('scrap_disposals', [
        'total_received' => 2200.00,
        'payment_method' => PaymentMethod::ScrapAdjustment->value,
        'supplier_id' => $this->supplier->id,
    ]);

    $disposal = ScrapDisposal::where('total_received', 2200.00)->first();

    // Verify scrap collection statuses are changed to Disposed
    $this->scrap1->refresh();
    expect($this->scrap1->status)->toBe('Disposed');

    // Verify SupplierLedger is credited (reducing supplier payable running balance)
    $this->assertDatabaseHas('supplier_ledgers', [
        'supplier_id' => $this->supplier->id,
        'transaction_type' => 'ScrapAdjustment',
        'debit' => 0.00,
        'credit' => 2200.00,
        'running_balance' => 2200.00, // credit increases running balance for supplier payable
        'reference_type' => ScrapDisposal::class,
        'reference_id' => $disposal->id,
    ]);

    // Verify NO cashbook entry is created
    $this->assertDatabaseMissing('cashbook_entries', [
        'reference_type' => ScrapDisposal::class,
        'reference_id' => $disposal->id,
    ]);
});

test('admin cannot dispose scrap for cash without active register session', function () {
    // Delete register session
    $this->session->delete();

    Livewire::test(CreateScrapDisposal::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'disposal_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::Cash->value,
            'total_received' => 2200.00,
            'collections' => [$this->scrap1->id, $this->scrap2->id],
        ])
        ->call('create')
        ->assertHasErrors(['payment_method']);
});
