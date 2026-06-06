<?php

use App\Enums\BatteryStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Models\BatterySerial;
use App\Models\Broker;
use App\Models\BrokerLedger;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ScrapCollection;
use App\Models\User;
use App\Models\Warehouse;
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

    $this->customer = Customer::create([
        'name' => 'John Customer',
        'mobile' => '01700000000',
        'customer_type' => 'Retail',
        'credit_limit' => 10000.00,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Showroom Gazipur',
        'location' => 'Gazipur',
    ]);

    $this->category = ProductCategory::create(['name' => 'IPS Batteries']);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Lucas',
        'model_name' => 'L-150',
        'mrp_price' => 10000.00,
        'dealer_price' => 9000.00,
        'set_price' => 8500.00,
        'standard_set_qty' => 4,
        'warranty_months' => 18,
    ]);

    $this->actingAs($this->admin);
});

test('redirects to cash register session if no active session exists', function () {
    Livewire::test(CreateInvoice::class)
        ->assertRedirect('/admin/cash-register-sessions');
});

test('can access create page if active session exists', function () {
    // Open cash session
    app(CashSessionService::class)->openSession($this->admin, 1000.00);

    Livewire::test(CreateInvoice::class)
        ->assertSuccessful();
});

test('can perform retail sale checkout successfully', function () {
    // Open cash session
    $session = app(CashSessionService::class)->openSession($this->admin, 1000.00);

    // Create in-stock serial
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'LUCAS-150-001',
        'current_status' => BatteryStatus::InStock,
    ]);

    // Record a purchase transaction so getCurrentWarehouse finds it
    app(InventoryService::class)->recordPurchase(
        $this->product,
        'LUCAS-150-001',
        $this->warehouse,
        $this->product, // dummy reference
        $this->admin
    );

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'is_set' => false,
                    'battery_serial_id' => $serial->id,
                    'sale_price' => 10000.00,
                    'warranty_months' => 18,
                ],
            ],
            'discount_amount' => 1000.00,
            'scrap_items' => [],
            'payments' => [
                [
                    'payment_method' => PaymentMethod::Cash->value,
                    'amount' => 5000.00,
                    'service_charge' => 0.00,
                ],
                [
                    'payment_method' => PaymentMethod::Bkash->value,
                    'amount' => 4000.00,
                    'service_charge' => 74.00, // 1.85% of 4000
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify invoice
    $invoice = Invoice::first();
    expect($invoice)->not->toBeNull()
        ->and($invoice->sub_total)->toBe('10000.00')
        ->and($invoice->discount_amount)->toBe('1000.00')
        ->and($invoice->scrap_adjustment)->toBe('0.00')
        ->and($invoice->grand_total)->toBe('9000.00');

    // Verify item
    $item = InvoiceItem::first();
    expect($item)->not->toBeNull()
        ->and($item->invoice_id)->toBe($invoice->id)
        ->and($item->product_id)->toBe($this->product->id)
        ->and($item->battery_serial_id)->toBe($serial->id)
        ->and($item->sale_price)->toBe('10000.00');

    // Verify serial updated to Sold
    expect($serial->refresh()->current_status)->toBe(BatteryStatus::Sold);

    // Verify inventory transaction
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => 'sale',
    ]);

    // Verify payments
    expect(Payment::count())->toBe(2);
    $cashPayment = Payment::where('payment_method', PaymentMethod::Cash)->first();
    expect($cashPayment)->not->toBeNull()
        ->and($cashPayment->total_amount)->toBe('5000.00');

    $bkashPayment = Payment::where('payment_method', PaymentMethod::Bkash)->first();
    expect($bkashPayment)->not->toBeNull()
        ->and($bkashPayment->total_amount)->toBe('4000.00')
        ->and($bkashPayment->service_charge)->toBe('74.00');

    // Verify payment allocations
    expect(PaymentAllocation::count())->toBe(2);
    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $cashPayment->id,
        'invoice_id' => $invoice->id,
        'allocated_amount' => 5000.00,
    ]);

    // Verify cashbook entries
    expect(CashbookEntry::count())->toBe(2);
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $session->id,
        'entry_type' => 'Sale',
        'direction' => TransactionDirection::In->value,
        'payment_method' => PaymentMethod::Cash->value,
        'amount' => 5000.00,
    ]);
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $session->id,
        'entry_type' => 'Sale',
        'direction' => TransactionDirection::In->value,
        'payment_method' => PaymentMethod::Bkash->value,
        'amount' => 4000.00,
    ]);

    // Verify customer ledger logs: debit invoice (9000), credit payments (5000, 4000)
    // Running balance should end up at 0
    $ledgerEntries = CustomerLedger::where('customer_id', $this->customer->id)->get();
    expect($ledgerEntries->count())->toBe(3)
        ->and($ledgerEntries[0]->transaction_type)->toBe('Invoice')
        ->and($ledgerEntries[0]->debit)->toBe('9000.00')
        ->and($ledgerEntries[0]->credit)->toBe('0.00')
        ->and($ledgerEntries[0]->running_balance)->toBe('9000.00')

        ->and($ledgerEntries[1]->transaction_type)->toBe('Payment')
        ->and($ledgerEntries[1]->debit)->toBe('0.00')
        ->and($ledgerEntries[1]->credit)->toBe('5000.00')
        ->and($ledgerEntries[1]->running_balance)->toBe('4000.00')

        ->and($ledgerEntries[2]->transaction_type)->toBe('Payment')
        ->and($ledgerEntries[2]->debit)->toBe('0.00')
        ->and($ledgerEntries[2]->credit)->toBe('4000.00')
        ->and($ledgerEntries[2]->running_balance)->toBe('0.00');
});

test('can perform set-based checkout successfully', function () {
    app(CashSessionService::class)->openSession($this->admin, 1000.00);

    // Create 4 in-stock serials
    $serials = [];
    for ($i = 1; $i <= 4; $i++) {
        $serials[] = BatterySerial::create([
            'product_id' => $this->product->id,
            'serial_no' => "LUCAS-150-SET-{$i}",
            'current_status' => BatteryStatus::InStock,
        ]);
        app(InventoryService::class)->recordPurchase(
            $this->product,
            "LUCAS-150-SET-{$i}",
            $this->warehouse,
            $this->product,
            $this->admin
        );
    }

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'is_set' => true,
                    'battery_serial_ids' => collect($serials)->pluck('id')->toArray(),
                    'sale_price' => 8500.00, // set price
                    'warranty_months' => 18,
                ],
            ],
            'discount_amount' => 0.00,
            'scrap_items' => [],
            'payments' => [
                [
                    'payment_method' => PaymentMethod::Cash->value,
                    'amount' => 34000.00, // 8500 * 4
                    'service_charge' => 0.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify invoice total
    $invoice = Invoice::first();
    expect($invoice->sub_total)->toBe('34000.00')
        ->and($invoice->grand_total)->toBe('34000.00');

    // Verify 4 invoice items created
    expect(InvoiceItem::count())->toBe(4);

    // Verify all serials sold
    foreach ($serials as $serial) {
        expect($serial->refresh()->current_status)->toBe(BatteryStatus::Sold);
    }
});

test('can perform checkout with scrap core deduction', function () {
    app(CashSessionService::class)->openSession($this->admin, 1000.00);

    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'LUCAS-150-SCRAP',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'LUCAS-150-SCRAP', $this->warehouse, $this->product, $this->admin);

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'is_set' => false,
                    'battery_serial_id' => $serial->id,
                    'sale_price' => 10000.00,
                    'warranty_months' => 18,
                ],
            ],
            'discount_amount' => 0.00,
            'scrap_items' => [
                [
                    'scrap_type' => 'EasyBike',
                    'quantity' => 1,
                    'unit_value' => 1500.00,
                    'warehouse_id' => $this->warehouse->id,
                ],
            ],
            'payments' => [
                [
                    'payment_method' => PaymentMethod::Cash->value,
                    'amount' => 8500.00, // 10000 - 1500 scrap
                    'service_charge' => 0.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify invoice scrap adjustment
    $invoice = Invoice::first();
    expect($invoice->scrap_adjustment)->toBe('1500.00')
        ->and($invoice->grand_total)->toBe('8500.00');

    // Verify scrap collection record
    $scrap = ScrapCollection::first();
    expect($scrap)->not->toBeNull()
        ->and($scrap->invoice_id)->toBe($invoice->id)
        ->and($scrap->customer_id)->toBe($this->customer->id)
        ->and($scrap->scrap_type)->toBe('EasyBike')
        ->and($scrap->quantity)->toBe('1.00')
        ->and($scrap->unit_value)->toBe('1500.00')
        ->and($scrap->total_value)->toBe('1500.00');
});

test('blocks checkout if credit limit is exceeded', function () {
    app(CashSessionService::class)->openSession($this->admin, 1000.00);

    // Limit credit to 2000
    $this->customer->update(['credit_limit' => 2000.00]);

    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'LUCAS-150-CREDIT',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'LUCAS-150-CREDIT', $this->warehouse, $this->product, $this->admin);

    // Subtotal 10000. Paid 5000. Due 5000 (exceeds credit limit of 2000)
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'is_set' => false,
                    'battery_serial_id' => $serial->id,
                    'sale_price' => 10000.00,
                    'warranty_months' => 18,
                ],
            ],
            'discount_amount' => 0.00,
            'scrap_items' => [],
            'payments' => [
                [
                    'payment_method' => PaymentMethod::Cash->value,
                    'amount' => 5000.00,
                    'service_charge' => 0.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['customer_id']); // custom rule on customer_id or wizard step validation
});

test('allocates broker commission successfully', function () {
    app(CashSessionService::class)->openSession($this->admin, 1000.00);

    $broker = Broker::create([
        'name' => 'Kabir Broker',
        'mobile' => '01800000000',
        'commission_rate' => 5.00, // 5%
    ]);

    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'LUCAS-150-BROKER',
        'current_status' => BatteryStatus::InStock,
    ]);
    app(InventoryService::class)->recordPurchase($this->product, 'LUCAS-150-BROKER', $this->warehouse, $this->product, $this->admin);

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'broker_id' => $broker->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'is_set' => false,
                    'battery_serial_id' => $serial->id,
                    'sale_price' => 10000.00,
                    'warranty_months' => 18,
                ],
            ],
            'discount_amount' => 0.00,
            'scrap_items' => [],
            'payments' => [
                [
                    'payment_method' => PaymentMethod::Cash->value,
                    'amount' => 10000.00,
                    'service_charge' => 0.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify invoice broker field is set
    $invoice = Invoice::first();
    expect($invoice->broker_id)->toBe($broker->id);

    // Verify broker ledger credit recorded
    $brokerLedger = BrokerLedger::where('broker_id', $broker->id)->first();
    expect($brokerLedger)->not->toBeNull()
        ->and($brokerLedger->transaction_type)->toBe('Commission')
        ->and($brokerLedger->credit)->toBe('500.00') // 5% of 10000
        ->and($brokerLedger->running_balance)->toBe('500.00');
});

test('reverts serials status and deletes transactions on invoice deletion', function () {
    app(CashSessionService::class)->openSession($this->admin, 1000.00);

    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'LUCAS-150-REVERT',
        'current_status' => BatteryStatus::InStock,
    ]);
    $purchaseInvoice = app(InventoryService::class)->recordPurchase($this->product, 'LUCAS-150-REVERT', $this->warehouse, $this->product, $this->admin);

    $invoice = Invoice::create([
        'invoice_no' => 'INV-TEST-REVERT',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => CashRegisterSession::whereNull('closed_at')->first()->id,
        'invoice_date' => now(),
        'sub_total' => 10000.00,
        'grand_total' => 10000.00,
        'created_by' => $this->admin->id,
    ]);

    $item = new InvoiceItem([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'sale_price' => 10000.00,
        'warranty_months' => 18,
    ]);
    // Set serial id
    $item->battery_serial_id = $serial->id;
    $item->save();

    // Directly record sale using InventoryService to mimic checkout effect
    app(InventoryService::class)->recordSale($serial, $this->warehouse, $invoice, $this->admin);

    expect($serial->refresh()->current_status)->toBe(BatteryStatus::Sold);

    // Now delete the invoice item or invoice (invoice item deletion should trigger status revert)
    $item->delete();

    expect($serial->refresh()->current_status)->toBe(BatteryStatus::InStock);
    $this->assertDatabaseMissing('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'reference_type' => Invoice::class,
        'reference_id' => $invoice->id,
    ]);
});
