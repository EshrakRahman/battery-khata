<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\LedgerService;
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

    $this->actingAs($this->admin);

    $this->customer = Customer::create([
        'name' => 'Kashem Driver',
        'mobile' => '01700998877',
        'customer_type' => 'Retail',
        'credit_limit' => 50000.00,
    ]);

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
        'expected_cash' => 5000.00,
    ]);
});

test('admin can list customer payments', function () {
    $payment = Payment::create([
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $this->session->id,
        'payment_date' => now(),
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 3000.00,
        'received_by' => $this->admin->id,
    ]);

    Livewire::test(ListPayments::class)
        ->assertCanSeeTableRecords([$payment])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('customer.name')
        ->assertCanRenderTableColumn('payment_date')
        ->assertCanRenderTableColumn('payment_method')
        ->assertCanRenderTableColumn('total_amount');
});

test('admin can create general payment which updates ledger cashbook and allocates via FIFO', function () {
    // 1. Create two completed unpaid invoices for the customer
    $invoice1 = Invoice::create([
        'invoice_no' => 'INV-001',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $this->session->id,
        'invoice_date' => now()->subDays(2),
        'sub_total' => 5000.00,
        'grand_total' => 5000.00,
        'invoice_status' => InvoiceStatus::Completed,
        'created_by' => $this->admin->id,
    ]);

    $invoice2 = Invoice::create([
        'invoice_no' => 'INV-002',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $this->session->id,
        'invoice_date' => now()->subDays(1),
        'sub_total' => 4000.00,
        'grand_total' => 4000.00,
        'invoice_status' => InvoiceStatus::Completed,
        'created_by' => $this->admin->id,
    ]);

    // Setup initial customer ledger debit balances
    $ledgerService = app(LedgerService::class);
    $ledgerService->recordCustomerTransaction($this->customer, 5000.00, 0.00, 'Invoice', $invoice1);
    $ledgerService->recordCustomerTransaction($this->customer, 4000.00, 0.00, 'Invoice', $invoice2);

    $this->customer->refresh();

    // 2. Submit customer credit payment of BDT 6500.00
    // This should fully cover invoice 1 (5000.00) and partially cover invoice 2 (1500.00)
    Livewire::test(CreatePayment::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::Cash->value,
            'total_amount' => 6500.00,
            'service_charge' => 0.00,
            'reference_no' => 'RCV-881',
            'notes' => 'FIFO settlement',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // 3. Verify Payment and allocations are created
    $this->assertDatabaseHas('payments', [
        'customer_id' => $this->customer->id,
        'total_amount' => 6500.00,
        'received_by' => $this->admin->id,
    ]);

    $payment = Payment::where('total_amount', 6500.00)->first();

    // Verify invoice 1 gets BDT 5000.00 allocated
    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $payment->id,
        'invoice_id' => $invoice1->id,
        'allocated_amount' => 5000.00,
    ]);

    // Verify invoice 2 gets BDT 1500.00 allocated
    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $payment->id,
        'invoice_id' => $invoice2->id,
        'allocated_amount' => 1500.00,
    ]);

    // 4. Verify Ledger updates (credit decreases running balance)
    // Starting balance = 5000 + 4000 = 9000.
    // Credit transaction = 6500.
    // Running balance should be = 9000 - 6500 = 2500.
    $this->assertDatabaseHas('customer_ledgers', [
        'customer_id' => $this->customer->id,
        'transaction_type' => 'Payment',
        'debit' => 0.00,
        'credit' => 6500.00,
        'running_balance' => 2500.00,
        'reference_type' => Payment::class,
        'reference_id' => $payment->id,
    ]);

    // 5. Verify CashbookEntry Inflow is logged
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $this->session->id,
        'entry_type' => 'Payment',
        'direction' => TransactionDirection::In->value,
        'payment_method' => PaymentMethod::Cash->value,
        'amount' => 6500.00,
        'reference_type' => Payment::class,
        'reference_id' => $payment->id,
    ]);
});

test('cannot load create payment page without active register session', function () {
    $this->session->delete();

    Livewire::test(CreatePayment::class)
        ->assertRedirect();
});
