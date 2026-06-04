<?php

use App\Models\Broker;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Cashier Rahim',
        'email' => 'rahim.cash@volt.com',
        'password' => bcrypt('secret'),
        'role' => 'counter_boy',
    ]);

    $this->customer = Customer::create([
        'name' => 'Kamil Khan',
        'mobile' => '01712345678',
        'customer_type' => 'retail',
        'credit_limit' => 50000.00,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Rimso Battery Co',
        'mobile' => '01999888777',
        'supplier_type' => 'manufacturer',
    ]);

    $this->broker = Broker::create([
        'name' => 'Dalal Selim',
        'mobile' => '01511223344',
    ]);

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->user->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Main Godown',
    ]);

    $this->service = new LedgerService;
});

test('customer ledger transaction increases running balance with debit and decreases with credit', function () {
    $invoice = Invoice::create([
        'invoice_no' => 'INV-001',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $this->session->id,
        'invoice_date' => now(),
        'grand_total' => 15000.00,
        'created_by' => $this->user->id,
    ]);

    // 1. First transaction: Debit of 15,000.00 (invoice sale)
    $entry1 = $this->service->recordCustomerTransaction(
        customer: $this->customer,
        debit: 15000.00,
        credit: 0.00,
        transactionType: 'Invoice',
        reference: $invoice,
        notes: 'Sale of Easy-bike battery set'
    );

    expect($entry1->running_balance)->toEqual(15000.00)
        ->and($entry1->debit)->toEqual(15000.00)
        ->and($entry1->credit)->toEqual(0.00);

    // 2. Second transaction: Credit of 10,000.00 (payment received)
    $entry2 = $this->service->recordCustomerTransaction(
        customer: $this->customer,
        debit: 0.00,
        credit: 10000.00,
        transactionType: 'Payment',
        reference: null,
        notes: 'Cash payment received'
    );

    expect($entry2->running_balance)->toEqual(5000.00)
        ->and($entry2->debit)->toEqual(0.00)
        ->and($entry2->credit)->toEqual(10000.00);

    // Assert DB persistence
    $this->assertDatabaseHas('customer_ledgers', [
        'id' => $entry1->id,
        'running_balance' => 15000.00,
    ]);

    $this->assertDatabaseHas('customer_ledgers', [
        'id' => $entry2->id,
        'running_balance' => 5000.00,
    ]);
});

test('supplier ledger transaction increases running balance with credit and decreases with debit', function () {
    $purchaseInvoice = PurchaseInvoice::create([
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_no' => 'PUR-001',
        'purchase_date' => now()->toDateString(),
        'created_by' => $this->user->id,
    ]);

    // 1. First transaction: Credit of 60,000.00 (purchase invoice)
    $entry1 = $this->service->recordSupplierTransaction(
        supplier: $this->supplier,
        debit: 0.00,
        credit: 60000.00,
        transactionType: 'PurchaseInvoice',
        reference: $purchaseInvoice,
        notes: 'Bought HAMKO batteries'
    );

    expect($entry1->running_balance)->toEqual(60000.00)
        ->and($entry1->debit)->toEqual(0.00)
        ->and($entry1->credit)->toEqual(60000.00);

    // 2. Second transaction: Debit of 40,000.00 (payment made)
    $entry2 = $this->service->recordSupplierTransaction(
        supplier: $this->supplier,
        debit: 40000.00,
        credit: 0.00,
        transactionType: 'SupplierPayment',
        reference: null,
        notes: 'Paid bank transfer'
    );

    expect($entry2->running_balance)->toEqual(20000.00)
        ->and($entry2->debit)->toEqual(40000.00)
        ->and($entry2->credit)->toEqual(0.00);

    $this->assertDatabaseHas('supplier_ledgers', [
        'id' => $entry1->id,
        'running_balance' => 60000.00,
    ]);

    $this->assertDatabaseHas('supplier_ledgers', [
        'id' => $entry2->id,
        'running_balance' => 20000.00,
    ]);
});

test('broker ledger transaction increases running balance with credit and decreases with debit', function () {
    // 1. First transaction: Credit of 1,200.00 (earned commission)
    $entry1 = $this->service->recordBrokerTransaction(
        broker: $this->broker,
        debit: 0.00,
        credit: 1200.00,
        transactionType: 'CommissionEarned',
        reference: null,
        notes: 'Customer referral commission'
    );

    expect($entry1->running_balance)->toEqual(1200.00)
        ->and($entry1->debit)->toEqual(0.00)
        ->and($entry1->credit)->toEqual(1200.00);

    // 2. Second transaction: Debit of 1,000.00 (commission payout)
    $entry2 = $this->service->recordBrokerTransaction(
        broker: $this->broker,
        debit: 1000.00,
        credit: 0.00,
        transactionType: 'CommissionPayout',
        reference: null,
        notes: 'Cash payout to broker'
    );

    expect($entry2->running_balance)->toEqual(200.00)
        ->and($entry2->debit)->toEqual(1000.00)
        ->and($entry2->credit)->toEqual(0.00);

    $this->assertDatabaseHas('broker_ledgers', [
        'id' => $entry1->id,
        'running_balance' => 1200.00,
    ]);

    $this->assertDatabaseHas('broker_ledgers', [
        'id' => $entry2->id,
        'running_balance' => 200.00,
    ]);
});
