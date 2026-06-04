<?php

use App\Enums\BatteryStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Filament\Resources\BatterySerials\Pages\CreateBatterySerial;
use App\Filament\Resources\BatterySerials\Pages\EditBatterySerial;
use App\Filament\Resources\BatterySerials\Pages\ListBatterySerials;
use App\Filament\Resources\BatterySerials\RelationManagers\TransactionsRelationManager;
use App\Models\BatterySerial;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
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

    $this->category = ProductCategory::create(['name' => 'EasyBike Batteries']);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Lucas',
        'model_name' => 'L-150',
        'mrp_price' => 11000.00,
        'dealer_price' => 9500.00,
    ]);

    $this->actingAs($this->admin);
});

test('can list battery serials', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'SN123456',
        'current_status' => BatteryStatus::InStock,
    ]);

    Livewire::test(ListBatterySerials::class)
        ->assertCanSeeTableRecords([$serial])
        ->assertCanRenderTableColumn('serial_no')
        ->assertCanRenderTableColumn('product.brand_name')
        ->assertCanRenderTableColumn('current_status');
});

test('can create a battery serial', function () {
    Livewire::test(CreateBatterySerial::class)
        ->fillForm([
            'product_id' => $this->product->id,
            'serial_no' => 'SN999999',
            'current_status' => 'in_stock',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(BatterySerial::where('serial_no', 'SN999999')->exists())->toBeTrue();
});

test('unique battery serial validation', function () {
    BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'SN123456',
        'current_status' => BatteryStatus::InStock,
    ]);

    Livewire::test(CreateBatterySerial::class)
        ->fillForm([
            'product_id' => $this->product->id,
            'serial_no' => 'SN123456',
            'current_status' => 'in_stock',
        ])
        ->call('create')
        ->assertHasFormErrors(['serial_no' => 'unique']);
});

test('can edit a battery serial', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'OLD12345',
        'current_status' => BatteryStatus::InStock,
    ]);

    Livewire::test(EditBatterySerial::class, [
        'record' => $serial->getKey(),
    ])
        ->fillForm([
            'serial_no' => 'NEW12345',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($serial->refresh()->serial_no)->toBe('NEW12345');
});

test('battery serial edit page shows related transaction history', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'SN_HIST_123',
        'current_status' => BatteryStatus::InStock,
    ]);

    $warehouse = Warehouse::create([
        'name' => 'Test Godown',
        'location' => 'Dhaka',
    ]);

    $transaction = InventoryTransaction::create([
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $warehouse->id,
        'transaction_type' => TransactionType::Purchase,
        'notes' => 'Received from supplier',
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(TransactionsRelationManager::class, [
        'ownerRecord' => $serial,
        'pageClass' => EditBatterySerial::class,
    ])
        ->assertCanSeeTableRecords([$transaction])
        ->assertCanRenderTableColumn('transaction_type')
        ->assertCanRenderTableColumn('warehouse.name');
});
