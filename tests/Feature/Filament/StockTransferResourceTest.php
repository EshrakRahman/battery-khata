<?php

use App\Enums\BatteryStatus;
use App\Enums\UserRole;
use App\Filament\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\EditStockTransfer;
use App\Models\BatterySerial;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
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

    $this->warehouseA = Warehouse::create([
        'name' => 'Tongi Main Godown',
        'location' => 'Tongi',
    ]);

    $this->warehouseB = Warehouse::create([
        'name' => 'Gazipur Showroom',
        'location' => 'Gazipur',
    ]);

    $this->category = ProductCategory::create(['name' => 'Car Batteries']);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Hamko',
        'model_name' => 'H-80',
        'mrp_price' => 8000.00,
        'dealer_price' => 7000.00,
    ]);

    $this->actingAs($this->admin);
});

test('source and destination warehouses must be different', function () {
    Livewire::test(CreateStockTransfer::class)
        ->fillForm([
            'source_warehouse_id' => $this->warehouseA->id,
            'destination_warehouse_id' => $this->warehouseA->id,
            'transfer_date' => today()->toDateString(),
            'status' => 'completed',
        ])
        ->call('create')
        ->assertHasFormErrors(['destination_warehouse_id']);
});

test('options for battery serials are filtered by source warehouse', function () {
    // Create serial in warehouse A
    $serialA = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'SERIAL-IN-A',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'SERIAL-IN-A', $this->warehouseA, $this->product, $this->admin);

    // Create serial in warehouse B
    $serialB = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'SERIAL-IN-B',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'SERIAL-IN-B', $this->warehouseB, $this->product, $this->admin);

    // Open Livewire form and check serial options
    $component = Livewire::test(CreateStockTransfer::class)
        ->set('data.source_warehouse_id', $this->warehouseA->id);

    // Assert that SERIAL-IN-A is in the options and SERIAL-IN-B is not
    $options = $component->instance()->form->getComponent('serial_ids')->getOptions();
    expect($options)->toHaveKey($serialA->id)
        ->and($options)->not->toHaveKey($serialB->id);
});

test('can perform direct completed stock transfer', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-DIRECT-001',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'HAMKO-DIRECT-001', $this->warehouseA, $this->product, $this->admin);

    Livewire::test(CreateStockTransfer::class)
        ->fillForm([
            'source_warehouse_id' => $this->warehouseA->id,
            'destination_warehouse_id' => $this->warehouseB->id,
            'transfer_date' => today()->toDateString(),
            'status' => 'completed',
            'serial_ids' => [$serial->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify stock transfer record
    $transfer = StockTransfer::first();
    expect($transfer)->not->toBeNull()
        ->and($transfer->status)->toBe('completed');

    // Verify both TransferOut and TransferIn transactions are recorded
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouseA->id,
        'transaction_type' => 'transfer_out',
        'reference_type' => StockTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouseB->id,
        'transaction_type' => 'transfer_in',
        'reference_type' => StockTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    // Verify current warehouse of serial is B
    expect(app(InventoryService::class)->getCurrentWarehouse($serial)->id)->toBe($this->warehouseB->id)
        ->and($serial->refresh()->current_status)->toBe(BatteryStatus::InStock);
});

test('can perform in-transit stock transfer and transition to completed', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-TRANSIT-002',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'HAMKO-TRANSIT-002', $this->warehouseA, $this->product, $this->admin);

    // 1. Create InTransit
    Livewire::test(CreateStockTransfer::class)
        ->fillForm([
            'source_warehouse_id' => $this->warehouseA->id,
            'destination_warehouse_id' => $this->warehouseB->id,
            'transfer_date' => today()->toDateString(),
            'status' => 'InTransit',
            'serial_ids' => [$serial->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $transfer = StockTransfer::first();
    expect($transfer->status)->toBe('InTransit');

    // Verify only TransferOut is created
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouseA->id,
        'transaction_type' => 'transfer_out',
    ]);

    $this->assertDatabaseMissing('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouseB->id,
        'transaction_type' => 'transfer_in',
    ]);

    // Current warehouse should be null (in transit)
    expect(app(InventoryService::class)->getCurrentWarehouse($serial))->toBeNull();

    // 2. Transition to Completed via Edit
    Livewire::test(EditStockTransfer::class, ['record' => $transfer->id])
        ->fillForm([
            'status' => 'completed',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($transfer->refresh()->status)->toBe('completed');

    // Verify TransferIn is now created
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouseB->id,
        'transaction_type' => 'transfer_in',
    ]);

    // Current warehouse should now be B
    expect(app(InventoryService::class)->getCurrentWarehouse($serial)->id)->toBe($this->warehouseB->id);
});

test('deleting a stock transfer reverts transactions and restores stock', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-DELETE-003',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'HAMKO-DELETE-003', $this->warehouseA, $this->product, $this->admin);

    // Create completed transfer
    $transfer = StockTransfer::create([
        'source_warehouse_id' => $this->warehouseA->id,
        'destination_warehouse_id' => $this->warehouseB->id,
        'transfer_date' => today()->toDateString(),
        'status' => 'completed',
        'created_by' => $this->admin->id,
    ]);

    // Create individual transactions
    app(InventoryService::class)->recordTransfer($serial, $this->warehouseA, $this->warehouseB, $transfer, $this->admin);

    expect(app(InventoryService::class)->getCurrentWarehouse($serial)->id)->toBe($this->warehouseB->id);

    // Mock deleting stock transfer
    // In our system, deleting the StockTransfer model should trigger deleting hooks to clear transactions
    $transfer->delete();

    // Verify transactions are deleted and serial is back in warehouse A
    $this->assertDatabaseMissing('inventory_transactions', [
        'reference_type' => StockTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    expect(app(InventoryService::class)->getCurrentWarehouse($serial)->id)->toBe($this->warehouseA->id);
});
