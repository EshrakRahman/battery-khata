<?php

use App\Enums\InvoiceStatus;
use App\Models\BatterySerial;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('POS invoices and items casting and soft delete traits', function () {
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
        'serial_no' => 'VOLVO-SALE-999',
    ]);

    $customer = Customer::create([
        'name' => 'Habib Rahman',
        'mobile' => '01700000000',
    ]);

    $user = User::create([
        'name' => 'Seller Aslam',
        'email' => 'aslam@sell.com',
        'password' => bcrypt('password'),
        'role' => 'counter_boy',
    ]);

    $session = CashRegisterSession::create([
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);

    $invoice = Invoice::create([
        'invoice_no' => 'INV-2026-0001',
        'customer_id' => $customer->id,
        'cash_register_session_id' => $session->id,
        'invoice_date' => now(),
        'sub_total' => 12000.00,
        'discount_amount' => 1000.00,
        'scrap_adjustment' => 0.00,
        'grand_total' => 11000.00,
        'invoice_status' => InvoiceStatus::Completed,
        'created_by' => $user->id,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'battery_serial_id' => $serial->id,
        'sale_price' => 12000.00,
        'warranty_months' => 18,
    ]);

    expect($invoice->invoice_status)->toBe(InvoiceStatus::Completed)
        ->and($invoice->grand_total)->toEqual(11000.00)
        ->and($invoice->customer->name)->toBe('Habib Rahman')
        ->and($item->product->model_name)->toBe('V90')
        ->and($item->serial->serial_no)->toBe('VOLVO-SALE-999');

    // Test soft delete
    $invoice->delete();
    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
});
