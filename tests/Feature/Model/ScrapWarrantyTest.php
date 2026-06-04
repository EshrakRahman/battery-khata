<?php

use App\Enums\ClaimStatus;
use App\Models\BatterySerial;
use App\Models\BufferBatteryIssue;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ScrapCollection;
use App\Models\ScrapDisposal;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarrantyClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('scrap core barter and bulk factory disposals', function () {
    $supplier = Supplier::create(['name' => 'Lucas Manufacturer']);
    $warehouse = Warehouse::create(['name' => 'Scrap Main Room']);
    $user = User::create([
        'name' => 'Manager Subrata',
        'email' => 'sub@volt.com',
        'password' => bcrypt('password'),
        'role' => 'manager',
    ]);

    $disposal = ScrapDisposal::create([
        'supplier_id' => $supplier->id,
        'disposal_date' => now()->toDateString(),
        'payment_method' => 'SupplierCredit',
        'total_received' => 45000.00,
        'created_by' => $user->id,
    ]);

    $collection = ScrapCollection::create([
        'warehouse_id' => $warehouse->id,
        'scrap_type' => 'EasyBike',
        'quantity' => '5.00',
        'estimated_weight' => '120.50',
        'unit_value' => 8000.00,
        'total_value' => 40000.00,
        'status' => 'DisposedToFactory',
        'scrap_disposal_id' => $disposal->id,
        'created_by' => $user->id,
    ]);

    expect($collection->scrapDisposal->total_received)->toEqual(45000.00)
        ->and($collection->quantity)->toEqual(5.00)
        ->and($collection->estimated_weight)->toEqual(120.50)
        ->and($collection->status)->toBe('DisposedToFactory');
});

test('warranty claims and service buffer battery issues', function () {
    $category = ProductCategory::create(['name' => 'IPS']);
    $product = Product::create([
        'category_id' => $category->id,
        'brand_name' => 'Lucas',
        'model_name' => 'LPS 150',
        'mrp_price' => 16000.00,
        'dealer_price' => 14500.00,
    ]);

    $faultySerial = BatterySerial::create(['product_id' => $product->id, 'serial_no' => 'LUCAS-BAD-01']);
    $replacementSerial = BatterySerial::create(['product_id' => $product->id, 'serial_no' => 'LUCAS-NEW-02']);
    $bufferSerial = BatterySerial::create(['product_id' => $product->id, 'serial_no' => 'LUCAS-BUFF-03']);

    $customer = Customer::create(['name' => 'Driver Hanif', 'mobile' => '01888777666']);
    $user = User::create([
        'name' => 'Counter Boy',
        'email' => 'boy@volt.com',
        'password' => bcrypt('password'),
        'role' => 'counter_boy',
    ]);
    $session = CashRegisterSession::create(['opened_by' => $user->id, 'opened_at' => now()]);

    $invoice = Invoice::create([
        'invoice_no' => 'INV-W-1',
        'customer_id' => $customer->id,
        'cash_register_session_id' => $session->id,
        'invoice_date' => now(),
        'grand_total' => 16000.00,
        'created_by' => $user->id,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'battery_serial_id' => $faultySerial->id,
        'sale_price' => 16000.00,
        'warranty_months' => 18,
    ]);

    $claim = WarrantyClaim::create([
        'invoice_item_id' => $item->id,
        'battery_serial_id' => $faultySerial->id,
        'claim_no' => 'CLAIM-2026-0001',
        'claim_date' => now()->toDateString(),
        'supplier_claim_no' => 'LUCAS-SLIP-102',
        'customer_issue' => 'Acid leak and voltage drop',
        'claim_status' => ClaimStatus::SentToSupplier,
        'replacement_battery_serial_id' => $replacementSerial->id,
        'created_by' => $user->id,
    ]);

    $buffer = BufferBatteryIssue::create([
        'warranty_claim_id' => $claim->id,
        'battery_serial_id' => $bufferSerial->id,
        'issued_date' => now()->toDateString(),
        'created_by' => $user->id,
    ]);

    expect($claim->claim_status)->toBe(ClaimStatus::SentToSupplier)
        ->and($claim->faultySerial->serial_no)->toBe('LUCAS-BAD-01')
        ->and($claim->replacementSerial->serial_no)->toBe('LUCAS-NEW-02')
        ->and($buffer->bufferSerial->serial_no)->toBe('LUCAS-BUFF-03');
});
