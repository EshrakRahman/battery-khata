<?php

use App\Enums\BatteryStatus;
use App\Enums\TransactionType;
use App\Exceptions\InvalidSerialStatusException;
use App\Models\BatterySerial;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = ProductCategory::create(['name' => 'Easy-Bike Batteries']);
    $this->product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Hamko',
        'model_name' => 'HPD 130',
        'mrp_price' => 14000.00,
        'dealer_price' => 12500.00,
    ]);
    $this->warehouse = Warehouse::create(['name' => 'Main Godown']);
    $this->showroom = Warehouse::create(['name' => 'Showroom Gazipur']);

    $this->user = User::create([
        'name' => 'Manager Rahim',
        'email' => 'rahim@counter.com',
        'password' => bcrypt('secret'),
        'role' => 'manager',
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Hamko Batteries Ltd',
        'mobile' => '01711122233',
        'supplier_type' => 'manufacturer',
    ]);

    $this->customer = Customer::create([
        'name' => 'Kashem Driver',
        'mobile' => '01811223344',
        'customer_type' => 'retail',
        'credit_limit' => 20000.00,
    ]);

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->user->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
    ]);

    $this->service = new InventoryService;
});

test('recording a purchase creates the serial number and logs transaction', function () {
    $purchaseInvoice = PurchaseInvoice::create([
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_no' => 'PUR-001',
        'purchase_date' => now()->toDateString(),
        'created_by' => $this->user->id,
    ]);

    $serial = $this->service->recordPurchase(
        product: $this->product,
        serialNo: 'HAMKO-HPD-1001',
        warehouse: $this->warehouse,
        referenceInvoice: $purchaseInvoice,
        createdBy: $this->user
    );

    expect($serial)->toBeInstanceOf(BatterySerial::class)
        ->and($serial->serial_no)->toBe('HAMKO-HPD-1001')
        ->and($serial->current_status)->toBe(BatteryStatus::InStock);

    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => TransactionType::Purchase->value,
        'reference_type' => PurchaseInvoice::class,
        'reference_id' => $purchaseInvoice->id,
    ]);
});

test('recording a sale updates serial status to sold and checks for availability', function () {
    // Create serial in stock
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-HPD-1002',
        'current_status' => BatteryStatus::InStock,
    ]);

    $invoice = Invoice::create([
        'invoice_no' => 'INV-001',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $this->session->id,
        'invoice_date' => now(),
        'created_by' => $this->user->id,
    ]);

    $this->service->recordSale(
        serial: $serial,
        warehouse: $this->showroom,
        referenceInvoice: $invoice,
        createdBy: $this->user
    );

    $serial->refresh();
    expect($serial->current_status)->toBe(BatteryStatus::Sold);

    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->showroom->id,
        'transaction_type' => TransactionType::Sale->value,
        'reference_type' => Invoice::class,
        'reference_id' => $invoice->id,
    ]);
});

test('recording a sale fails if the serial is already sold', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-HPD-1003',
        'current_status' => BatteryStatus::Sold, // already sold
    ]);

    $invoice = Invoice::create([
        'invoice_no' => 'INV-002',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $this->session->id,
        'invoice_date' => now(),
        'created_by' => $this->user->id,
    ]);

    $this->expectException(InvalidSerialStatusException::class);
    $this->expectExceptionMessage('Battery serial HAMKO-HPD-1003 is already sold and cannot be sold again.');

    $this->service->recordSale(
        serial: $serial,
        warehouse: $this->showroom,
        referenceInvoice: $invoice,
        createdBy: $this->user
    );
});

test('buffer battery issue and return cycle validations', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-HPD-BUFF',
        'current_status' => BatteryStatus::InStock,
    ]);

    // 1. Issue buffer
    $this->service->recordBufferIssue(
        serial: $serial,
        warehouse: $this->showroom,
        referenceId: 99, // mocked warranty claim id
        createdBy: $this->user
    );

    $serial->refresh();
    expect($serial->current_status)->toBe(BatteryStatus::BufferIssued);

    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->showroom->id,
        'transaction_type' => TransactionType::BufferIssue->value,
    ]);

    // 2. Return buffer
    $this->service->recordBufferReturn(
        serial: $serial,
        warehouse: $this->showroom,
        referenceId: 99,
        createdBy: $this->user
    );

    $serial->refresh();
    expect($serial->current_status)->toBe(BatteryStatus::InStock);

    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->showroom->id,
        'transaction_type' => TransactionType::BufferReturn->value,
    ]);
});

test('stock transfer movements log source and destination transactions', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-HPD-TRANS',
        'current_status' => BatteryStatus::InStock,
    ]);

    $transfer = StockTransfer::create([
        'source_warehouse_id' => $this->warehouse->id,
        'destination_warehouse_id' => $this->showroom->id,
        'transfer_date' => now()->toDateString(),
        'status' => 'completed',
        'created_by' => $this->user->id,
    ]);

    $this->service->recordTransfer(
        serial: $serial,
        source: $this->warehouse,
        destination: $this->showroom,
        transfer: $transfer,
        createdBy: $this->user
    );

    // Verify both TransferOut (source) and TransferIn (destination) transactions exist
    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => TransactionType::TransferOut->value,
        'reference_type' => StockTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    $this->assertDatabaseHas('inventory_transactions', [
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->showroom->id,
        'transaction_type' => TransactionType::TransferIn->value,
        'reference_type' => StockTransfer::class,
        'reference_id' => $transfer->id,
    ]);
});

test('deriving serial current location dynamically from latest transaction', function () {
    $serial = BatterySerial::create([
        'product_id' => $this->product->id,
        'serial_no' => 'HAMKO-HPD-DYNAMIC',
        'current_status' => BatteryStatus::InStock,
    ]);

    // Move to Godown (Tongi)
    InventoryTransaction::create([
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->warehouse->id,
        'transaction_type' => TransactionType::Purchase,
        'created_by' => $this->user->id,
    ]);

    expect($this->service->getCurrentWarehouse($serial)->id)->toBe($this->warehouse->id);

    // Move to Showroom (Gazipur) via TransferIn
    InventoryTransaction::create([
        'battery_serial_id' => $serial->id,
        'warehouse_id' => $this->showroom->id,
        'transaction_type' => TransactionType::TransferIn,
        'created_by' => $this->user->id,
    ]);

    expect($this->service->getCurrentWarehouse($serial)->id)->toBe($this->showroom->id);
});
