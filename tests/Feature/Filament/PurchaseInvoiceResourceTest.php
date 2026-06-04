<?php

use App\Enums\BatteryStatus;
use App\Enums\UserRole;
use App\Filament\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\EditPurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\ListPurchaseInvoices;
use App\Models\BatterySerial;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Supplier;
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

    $this->supplier = Supplier::create([
        'name' => 'Hamko Batteries Ltd',
        'mobile' => '01711122233',
        'supplier_type' => 'Manufacturer',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Main Godown',
        'location' => 'Tongi, Gazipur',
    ]);

    $this->category = ProductCategory::create(['name' => 'EasyBike Batteries']);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Hamko',
        'model_name' => 'HPD-130',
        'mrp_price' => 14000.00,
        'dealer_price' => 12500.00,
    ]);

    $this->actingAs($this->admin);
});

test('can list purchase invoices', function () {
    $invoice = PurchaseInvoice::create([
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_no' => 'PUR-2024-001',
        'purchase_date' => today()->toDateString(),
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(ListPurchaseInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertCanRenderTableColumn('invoice_no')
        ->assertCanRenderTableColumn('supplier.name')
        ->assertCanRenderTableColumn('warehouse.name')
        ->assertCanRenderTableColumn('grand_total');
});

test('can create a purchase invoice without items', function () {
    Livewire::test(CreatePurchaseInvoice::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PUR-2024-002',
            'purchase_date' => today()->toDateString(),
            'notes' => 'Test purchase',
            'sub_total' => '0.00',
            'discount_amount' => '0.00',
            'grand_total' => '0.00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(PurchaseInvoice::where('invoice_no', 'PUR-2024-002')->exists())->toBeTrue();
});

test('invoice_no is required on creation', function () {
    Livewire::test(CreatePurchaseInvoice::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => '',
            'purchase_date' => today()->toDateString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['invoice_no' => 'required']);
});

test('invoice_no must be unique', function () {
    PurchaseInvoice::create([
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_no' => 'PUR-DUPE-001',
        'purchase_date' => today()->toDateString(),
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(CreatePurchaseInvoice::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_no' => 'PUR-DUPE-001',
            'purchase_date' => today()->toDateString(),
            'sub_total' => '0.00',
            'discount_amount' => '0.00',
            'grand_total' => '0.00',
        ])
        ->call('create')
        ->assertHasFormErrors(['invoice_no' => 'unique']);
});

test('can edit a purchase invoice', function () {
    $invoice = PurchaseInvoice::create([
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_no' => 'PUR-2024-003',
        'purchase_date' => today()->toDateString(),
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(EditPurchaseInvoice::class, ['record' => $invoice->getKey()])
        ->fillForm(['notes' => 'Updated notes'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($invoice->refresh()->notes)->toBe('Updated notes');
});

test('creating purchase invoice with items creates battery serial and inventory transaction', function () {
    $invoice = PurchaseInvoice::create([
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_no' => 'PUR-2024-INV',
        'purchase_date' => today()->toDateString(),
        'created_by' => $this->admin->id,
    ]);

    // Simulate the model event path by directly creating a PurchaseItem
    // with a serial_no set, which triggers the booted() creating listener
    $item = new PurchaseItem([
        'purchase_invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'purchase_price' => 12000.00,
    ]);
    $item->serial_no = 'HAMKO-HPD-TEST-001';
    $item->save();

    // Verify the battery serial was created in stock
    expect(BatterySerial::where('serial_no', 'HAMKO-HPD-TEST-001')->exists())->toBeTrue();

    $serial = BatterySerial::where('serial_no', 'HAMKO-HPD-TEST-001')->first();
    expect($serial->current_status)->toBe(BatteryStatus::InStock);

    // Verify an inventory transaction was logged
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouse->id,
        'reference_type' => PurchaseInvoice::class,
        'reference_id' => $invoice->id,
    ]);
});

test('deleting a purchase item marks battery serial as supplier returned', function () {
    $invoice = PurchaseInvoice::create([
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_no' => 'PUR-2024-DEL',
        'purchase_date' => today()->toDateString(),
        'created_by' => $this->admin->id,
    ]);

    $item = new PurchaseItem([
        'purchase_invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'purchase_price' => 12000.00,
    ]);
    $item->serial_no = 'HAMKO-HPD-DELETE-002';
    $item->save();

    $serial = BatterySerial::where('serial_no', 'HAMKO-HPD-DELETE-002')->first();
    expect($serial->current_status)->toBe(BatteryStatus::InStock);

    // Now delete the item — this should trigger the deleting listener
    $item->delete();

    expect($serial->refresh()->current_status)->toBe(BatteryStatus::SupplierReturned);
    $this->assertDatabaseMissing('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'reference_type' => PurchaseInvoice::class,
        'reference_id' => $invoice->id,
    ]);
});
