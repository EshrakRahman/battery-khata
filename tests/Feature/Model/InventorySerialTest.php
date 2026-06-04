<?php

use App\Enums\BatteryStatus;
use App\Enums\TransactionType;
use App\Models\BatterySerial;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('battery serials map to products and cast their status enum', function () {
    $category = ProductCategory::create(['name' => 'Automotive']);
    $product = Product::create([
        'category_id' => $category->id,
        'brand_name' => 'Volvo',
        'model_name' => 'V90',
        'mrp_price' => 12000.00,
        'dealer_price' => 10500.00,
    ]);

    $serial = BatterySerial::create([
        'product_id' => $product->id,
        'serial_no' => 'VOLVO-123456',
        'current_status' => BatteryStatus::InStock, // casting testing
    ]);

    expect($serial->product->model_name)->toBe('V90');
    expect($serial->current_status)->toBe(BatteryStatus::InStock);

    // Enforce unique serial numbers
    $this->expectException(QueryException::class);
    BatterySerial::create([
        'product_id' => $product->id,
        'serial_no' => 'VOLVO-123456',
    ]);
});

test('warehouse stock transfers and transaction log relationships', function () {
    $category = ProductCategory::create(['name' => 'Automotive']);
    $product = Product::create([
        'category_id' => $category->id,
        'brand_name' => 'Volvo',
        'model_name' => 'V90',
        'mrp_price' => 12000.00,
        'dealer_price' => 10500.00,
    ]);

    $serial = BatterySerial::create([
        'product_id' => $product->id,
        'serial_no' => 'VOLVO-TRANS-789',
    ]);

    $showroom = Warehouse::create(['name' => 'Gazipur Showroom']);
    $godown = Warehouse::create(['name' => 'Main Tongi Godown']);

    $user = User::create([
        'name' => 'Aslam CounterBoy',
        'email' => 'aslam@battery-khata.com',
        'password' => bcrypt('password123'),
        'role' => 'counter_boy',
    ]);

    $transfer = StockTransfer::create([
        'source_warehouse_id' => $godown->id,
        'destination_warehouse_id' => $showroom->id,
        'transfer_date' => now()->toDateString(),
        'status' => 'completed',
        'notes' => 'Tongi to Gazipur transfer',
        'created_by' => $user->id,
    ]);

    $transaction = InventoryTransaction::create([
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $showroom->id,
        'transaction_type' => TransactionType::TransferIn,
        'reference_type' => 'StockTransfer',
        'reference_id' => $transfer->id,
        'notes' => 'Received from main Tongi godown',
        'created_by' => $user->id,
    ]);

    expect($transfer->sourceWarehouse->name)->toBe('Main Tongi Godown')
        ->and($transfer->destinationWarehouse->name)->toBe('Gazipur Showroom')
        ->and($transfer->creator->name)->toBe('Aslam CounterBoy');

    expect($transaction->serial->serial_no)->toBe('VOLVO-TRANS-789')
        ->and($transaction->warehouse->name)->toBe('Gazipur Showroom')
        ->and($transaction->transaction_type)->toBe(TransactionType::TransferIn)
        ->and($transaction->creator->name)->toBe('Aslam CounterBoy');
});
