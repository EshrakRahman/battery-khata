<?php

use App\Enums\PaymentMethod;
use App\Models\Broker;
use App\Models\BrokerLedger;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('payments and allocation pivots relationships', function () {
    $customer = Customer::create(['name' => 'Kamil Khan', 'mobile' => '01712345678']);
    $user = User::create([
        'name' => 'Seller',
        'email' => 'seller@volt.com',
        'password' => bcrypt('password'),
        'role' => 'counter_boy',
    ]);
    $session = CashRegisterSession::create(['opened_by' => $user->id, 'opened_at' => now()]);

    $invoice = Invoice::create([
        'invoice_no' => 'INV-PAY-1',
        'customer_id' => $customer->id,
        'cash_register_session_id' => $session->id,
        'invoice_date' => now(),
        'grand_total' => 10000.00,
        'created_by' => $user->id,
    ]);

    $payment = Payment::create([
        'customer_id' => $customer->id,
        'cash_register_session_id' => $session->id,
        'payment_date' => now(),
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 10000.00,
        'service_charge' => 0.00,
        'received_by' => $user->id,
    ]);

    $allocation = PaymentAllocation::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'allocated_amount' => 10000.00,
    ]);

    expect($payment->payment_method)->toBe(PaymentMethod::Cash)
        ->and($payment->allocations)->toHaveCount(1)
        ->and($payment->allocations->first()->allocated_amount)->toEqual(10000.00)
        ->and($payment->allocations->first()->invoice->invoice_no)->toBe('INV-PAY-1');
});

test('customer supplier and broker ledger balances casting', function () {
    $customer = Customer::create(['name' => 'Kamil Khan', 'mobile' => '01712345678']);
    $supplier = Supplier::create(['name' => 'Rimso Battery Co']);
    $broker = Broker::create(['name' => 'Broker Habib']);

    $customerLedger = CustomerLedger::create([
        'customer_id' => $customer->id,
        'transaction_date' => now(),
        'transaction_type' => 'Invoice',
        'debit' => '25000.50',
        'credit' => '0.00',
        'running_balance' => '25000.50',
    ]);

    $supplierLedger = SupplierLedger::create([
        'supplier_id' => $supplier->id,
        'transaction_date' => now(),
        'transaction_type' => 'PurchaseInvoice',
        'debit' => '0.00',
        'credit' => '65000.00',
        'running_balance' => '65000.00',
    ]);

    $brokerLedger = BrokerLedger::create([
        'broker_id' => $broker->id,
        'transaction_date' => now(),
        'transaction_type' => 'CommissionCredit',
        'debit' => '0.00',
        'credit' => '1500.00',
        'running_balance' => '1500.00',
    ]);

    expect($customerLedger->debit)->toEqual(25000.50)
        ->and($customerLedger->running_balance)->toEqual(25000.50);

    expect($supplierLedger->credit)->toEqual(65000.00)
        ->and($supplierLedger->running_balance)->toEqual(65000.00);

    expect($brokerLedger->credit)->toEqual(1500.00)
        ->and($brokerLedger->running_balance)->toEqual(1500.00);
});
