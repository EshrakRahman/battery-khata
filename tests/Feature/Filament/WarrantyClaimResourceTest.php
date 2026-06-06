<?php

use App\Enums\BatteryStatus;
use App\Enums\ClaimStatus;
use App\Enums\UserRole;
use App\Filament\Resources\WarrantyClaims\Pages\CreateWarrantyClaim;
use App\Filament\Resources\WarrantyClaims\Pages\EditWarrantyClaim;
use App\Models\BatterySerial;
use App\Models\BufferBatteryIssue;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarrantyClaim;
use App\Services\CashSessionService;
use App\Services\InventoryService;
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

    $this->warehouse = Warehouse::create([
        'name' => 'Main Warehouse',
        'location' => 'Dhaka',
    ]);

    $this->category = ProductCategory::create(['name' => 'IPS Batteries']);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Luminous',
        'model_name' => 'L-150',
        'mrp_price' => 15000.00,
        'dealer_price' => 13000.00,
        'warranty_months' => 18,
    ]);

    $this->customer = Customer::create([
        'name' => 'Eshrak Rahman',
        'mobile' => '01712345678',
        'customer_type' => 'Retail',
        'credit_limit' => 50000.00,
    ]);

    // Create a sold battery
    $this->faultySerial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'FAULTY-BATTERY-123',
        'current_status' => BatteryStatus::InStock,
    ]);

    // Record purchase & sale
    $session = app(CashSessionService::class)->openSession($this->admin, 1000.00);

    $inventoryService = app(InventoryService::class);
    $invoiceMock = Invoice::create([
        'invoice_no' => 'INV-MOCK',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $session->id,
        'invoice_date' => now(),
        'sub_total' => 15000.00,
        'grand_total' => 15000.00,
        'created_by' => $this->admin->id,
    ]);

    $inventoryService->recordPurchase($this->product, 'FAULTY-BATTERY-123', $this->warehouse, $invoiceMock, $this->admin);
    $inventoryService->recordSale($this->faultySerial, $this->warehouse, $invoiceMock, $this->admin);

    $this->invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoiceMock->id,
        'product_id' => $this->product->id,
        'battery_serial_id' => $this->faultySerial->id,
        'sale_price' => 15000.00,
        'warranty_months' => 18,
    ]);

    // Create a buffer battery serial in stock
    $this->bufferSerial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'BUFFER-BATTERY-999',
        'current_status' => BatteryStatus::InStock,
    ]);
    $inventoryService->recordPurchase($this->product, 'BUFFER-BATTERY-999', $this->warehouse, $invoiceMock, $this->admin);

    // Create a replacement battery serial in stock
    $this->replacementSerial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'REPLACEMENT-BATTERY-777',
        'current_status' => BatteryStatus::InStock,
    ]);
    $inventoryService->recordPurchase($this->product, 'REPLACEMENT-BATTERY-777', $this->warehouse, $invoiceMock, $this->admin);

    $this->actingAs($this->admin);
});

test('only sold serials are listed as options for faulty battery serial', function () {
    $component = Livewire::test(CreateWarrantyClaim::class);

    $options = $component->instance()->form->getComponent('battery_serial_id')->getOptions();

    // Faulty serial (Sold) should be option, buffer and replacement serials (InStock) should not be options
    expect($options)->toHaveKey($this->faultySerial->id)
        ->and($options)->not->toHaveKey($this->bufferSerial->id)
        ->and($options)->not->toHaveKey($this->replacementSerial->id);
});

test('can create warranty claim in received status and log WarrantyIn transaction', function () {
    Livewire::test(CreateWarrantyClaim::class)
        ->fillForm([
            'battery_serial_id' => $this->faultySerial->id,
            'claim_date' => today()->toDateString(),
            'customer_issue' => 'Not holding charge',
            'warehouse_id' => $this->warehouse->id,
            'issue_buffer' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify claim record
    $claim = WarrantyClaim::first();
    expect($claim)->not->toBeNull()
        ->and($claim->claim_status)->toBe(ClaimStatus::Received)
        ->and($claim->invoice_item_id)->toBe($this->invoiceItem->id);

    // Verify faulty serial status is updated to WarrantyClaim
    expect($this->faultySerial->refresh()->current_status)->toBe(BatteryStatus::WarrantyClaim);

    // Verify WarrantyIn transaction is recorded
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $this->faultySerial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => 'warranty_in',
        'reference_type' => WarrantyClaim::class,
        'reference_id' => $claim->id,
    ]);
});

test('can create warranty claim and issue buffer battery', function () {
    Livewire::test(CreateWarrantyClaim::class)
        ->fillForm([
            'battery_serial_id' => $this->faultySerial->id,
            'claim_date' => today()->toDateString(),
            'customer_issue' => 'Not holding charge',
            'warehouse_id' => $this->warehouse->id,
            'issue_buffer' => true,
            'buffer_battery_serial_id' => $this->bufferSerial->id,
            'buffer_notes' => 'Temporary replacement battery',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $claim = WarrantyClaim::first();
    expect($claim)->not->toBeNull();

    // Verify buffer issue is recorded
    $bufferIssue = BufferBatteryIssue::first();
    expect($bufferIssue)->not->toBeNull()
        ->and($bufferIssue->warranty_claim_id)->toBe($claim->id)
        ->and($bufferIssue->battery_serial_id)->toBe($this->bufferSerial->id)
        ->and($bufferIssue->returned_date)->toBeNull();

    // Verify buffer serial status is updated to BufferIssued
    expect($this->bufferSerial->refresh()->current_status)->toBe(BatteryStatus::BufferIssued);

    // Verify BufferIssue transaction is recorded
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $this->bufferSerial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => 'buffer_issue',
        'reference_type' => 'WarrantyClaim',
        'reference_id' => $claim->id,
    ]);
});

test('deleting a warranty claim reverts transactions and restores serial statuses', function () {
    // 1. Create a claim
    $claim = WarrantyClaim::create([
        'invoice_item_id' => $this->invoiceItem->id,
        'battery_serial_id' => $this->faultySerial->id,
        'claim_no' => 'WRN-TEST',
        'claim_date' => today(),
        'customer_issue' => 'Fails under load',
        'claim_status' => ClaimStatus::Received,
        'created_by' => $this->admin->id,
    ]);

    $inventoryService = app(InventoryService::class);
    $inventoryService->recordWarrantyIn($this->faultySerial, $this->warehouse, $claim, $this->admin);

    // Issue a buffer too
    $buffer = BufferBatteryIssue::create([
        'warranty_claim_id' => $claim->id,
        'battery_serial_id' => $this->bufferSerial->id,
        'issued_date' => today(),
        'created_by' => $this->admin->id,
    ]);
    $inventoryService->recordBufferIssue($this->bufferSerial, $this->warehouse, $claim->id, $this->admin);

    expect($this->faultySerial->refresh()->current_status)->toBe(BatteryStatus::WarrantyClaim)
        ->and($this->bufferSerial->refresh()->current_status)->toBe(BatteryStatus::BufferIssued);

    // 2. Delete the claim
    $claim->delete();

    // Verify claim and buffer records are deleted
    $this->assertDatabaseMissing('warranty_claims', ['id' => $claim->id]);
    $this->assertDatabaseMissing('buffer_battery_issues', ['id' => $buffer->id]);

    // Verify statuses are restored
    expect($this->faultySerial->refresh()->current_status)->toBe(BatteryStatus::Sold)
        ->and($this->bufferSerial->refresh()->current_status)->toBe(BatteryStatus::InStock);

    // Verify transactions are deleted
    $this->assertDatabaseMissing('inventory_transactions', [
        'reference_type' => WarrantyClaim::class,
        'reference_id' => $claim->id,
    ]);
    $this->assertDatabaseMissing('inventory_transactions', [
        'reference_type' => 'WarrantyClaim',
        'reference_id' => $claim->id,
    ]);
});

test('can transition claim to sent_to_supplier', function () {
    $claim = WarrantyClaim::create([
        'invoice_item_id' => $this->invoiceItem->id,
        'battery_serial_id' => $this->faultySerial->id,
        'claim_no' => 'WRN-TRANSITION',
        'claim_date' => today(),
        'customer_issue' => 'Not charging',
        'claim_status' => ClaimStatus::Received,
        'created_by' => $this->admin->id,
    ]);

    app(InventoryService::class)->recordWarrantyIn($this->faultySerial, $this->warehouse, $claim, $this->admin);

    Livewire::test(EditWarrantyClaim::class, ['record' => $claim->id])
        ->callAction('send_to_supplier', [
            'supplier_sent_date' => today()->toDateString(),
            'supplier_claim_no' => 'SLIP-888',
        ])
        ->assertHasNoActionErrors();

    expect($claim->refresh())
        ->claim_status->toBe(ClaimStatus::SentToSupplier)
        ->supplier_claim_no->toBe('SLIP-888')
        ->supplier_sent_date->toDateString()->toBe(today()->toDateString());
});

test('can resolve claim via replacement and return buffer', function () {
    // 1. Create Received claim
    $claim = WarrantyClaim::create([
        'invoice_item_id' => $this->invoiceItem->id,
        'battery_serial_id' => $this->faultySerial->id,
        'claim_no' => 'WRN-REPLACEMENT',
        'claim_date' => today(),
        'customer_issue' => 'No power',
        'claim_status' => ClaimStatus::Received,
        'created_by' => $this->admin->id,
    ]);

    app(InventoryService::class)->recordWarrantyIn($this->faultySerial, $this->warehouse, $claim, $this->admin);

    // 2. Issue buffer
    $buffer = BufferBatteryIssue::create([
        'warranty_claim_id' => $claim->id,
        'battery_serial_id' => $this->bufferSerial->id,
        'issued_date' => today(),
        'created_by' => $this->admin->id,
    ]);
    app(InventoryService::class)->recordBufferIssue($this->bufferSerial, $this->warehouse, $claim->id, $this->admin);

    // 3. Move to SentToSupplier
    $claim->update([
        'claim_status' => ClaimStatus::SentToSupplier,
        'supplier_claim_no' => 'SLIP-REPLACEMENT',
        'supplier_sent_date' => today(),
    ]);

    // 4. Log supplier response as approved
    Livewire::test(EditWarrantyClaim::class, ['record' => $claim->id])
        ->callAction('supplier_response', [
            'response' => 'approved',
            'resolution_notes' => 'Supplier approved replacement',
        ])
        ->assertHasNoActionErrors();

    expect($claim->refresh()->claim_status)->toBe(ClaimStatus::Approved);

    // 5. Resolve claim
    Livewire::test(EditWarrantyClaim::class, ['record' => $claim->id])
        ->callAction('resolve_claim', [
            'replacement_battery_serial_id' => $this->replacementSerial->id,
            'warehouse_id' => $this->warehouse->id,
            'resolved_date' => today()->toDateString(),
            'resolution_notes' => 'Issued replacement to client',
            'buffer_returned_date' => today()->toDateString(),
            'buffer_return_warehouse_id' => $this->warehouse->id,
        ])
        ->assertHasNoActionErrors();

    // Verify claim details
    $claim->refresh();
    expect($claim->claim_status)->toBe(ClaimStatus::Resolved)
        ->and($claim->replacement_battery_serial_id)->toBe($this->replacementSerial->id)
        ->and($claim->resolved_date->toDateString())->toBe(today()->toDateString());

    // Verify faulty battery is Scrap
    expect($this->faultySerial->refresh()->current_status)->toBe(BatteryStatus::Scrap);

    // Verify replacement serial is Sold
    expect($this->replacementSerial->refresh()->current_status)->toBe(BatteryStatus::Sold);

    // Verify buffer battery is returned to stock
    expect($this->bufferSerial->refresh()->current_status)->toBe(BatteryStatus::InStock)
        ->and($buffer->refresh()->returned_date->toDateString())->toBe(today()->toDateString());

    // Verify inventory transactions
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $this->replacementSerial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => 'warranty_out',
    ]);

    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $this->bufferSerial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => 'buffer_return',
    ]);
});

test('can resolve claim via return original', function () {
    // 1. Create claim
    $claim = WarrantyClaim::create([
        'invoice_item_id' => $this->invoiceItem->id,
        'battery_serial_id' => $this->faultySerial->id,
        'claim_no' => 'WRN-RETURN',
        'claim_date' => today(),
        'claim_status' => ClaimStatus::Received,
        'created_by' => $this->admin->id,
    ]);

    app(InventoryService::class)->recordWarrantyIn($this->faultySerial, $this->warehouse, $claim, $this->admin);

    // 2. Set to SentToSupplier, then Rejected
    $claim->update(['claim_status' => ClaimStatus::SentToSupplier]);
    $claim->update(['claim_status' => ClaimStatus::Rejected]);

    // 3. Resolve by returning original faulty battery
    Livewire::test(EditWarrantyClaim::class, ['record' => $claim->id])
        ->callAction('resolve_claim', [
            'resolved_date' => today()->toDateString(),
            'resolution_notes' => 'Returned faulty battery to client',
        ])
        ->assertHasNoActionErrors();

    // Verify claim details
    $claim->refresh();
    expect($claim->claim_status)->toBe(ClaimStatus::Resolved)
        ->and($claim->resolved_date->toDateString())->toBe(today()->toDateString());

    // Verify faulty battery is Sold again
    expect($this->faultySerial->refresh()->current_status)->toBe(BatteryStatus::Sold);

    // Verify WarrantyOut transaction is recorded
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $this->faultySerial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => 'warranty_out',
    ]);
});

test('deleting a buffer issue reverts transactions and restores serial status', function () {
    // 1. Create claim and issue buffer
    $claim = WarrantyClaim::create([
        'invoice_item_id' => $this->invoiceItem->id,
        'battery_serial_id' => $this->faultySerial->id,
        'claim_no' => 'WRN-BUFFER-DEL',
        'claim_date' => today(),
        'claim_status' => ClaimStatus::Received,
        'created_by' => $this->admin->id,
    ]);

    $buffer = BufferBatteryIssue::create([
        'warranty_claim_id' => $claim->id,
        'battery_serial_id' => $this->bufferSerial->id,
        'issued_date' => today(),
        'created_by' => $this->admin->id,
    ]);
    app(InventoryService::class)->recordBufferIssue($this->bufferSerial, $this->warehouse, $claim->id, $this->admin);

    expect($this->bufferSerial->refresh()->current_status)->toBe(BatteryStatus::BufferIssued);

    // 2. Delete buffer issue
    $buffer->delete();

    // Verify buffer record is deleted
    $this->assertDatabaseMissing('buffer_battery_issues', ['id' => $buffer->id]);

    // Verify buffer status is restored
    expect($this->bufferSerial->refresh()->current_status)->toBe(BatteryStatus::InStock);

    // Verify buffer transaction is deleted
    $this->assertDatabaseMissing('inventory_transactions', [
        'battery_serial_id' => $this->bufferSerial->id,
        'transaction_type' => 'buffer_issue',
        'reference_type' => 'WarrantyClaim',
        'reference_id' => $claim->id,
    ]);
});
