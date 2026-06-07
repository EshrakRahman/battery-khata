<?php

use App\Enums\PaymentMethod;
use App\Enums\PdcStatus;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Widgets\BusinessOverviewStats;
use App\Filament\Widgets\CollectionsChart;
use App\Filament\Widgets\DueCustomersWidget;
use App\Filament\Widgets\PostDatedChequesWidget;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\PostDatedCheque;
use App\Models\User;
use Filament\Pages\Dashboard;
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

    $this->counterBoy = User::create([
        'name' => 'Counter Boy',
        'email' => 'boy@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::CounterBoy,
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'Kashem Driver',
        'mobile' => '01700998877',
        'customer_type' => 'Retail',
    ]);
});

test('dashboard page and widgets render for admin', function () {
    $this->actingAs($this->admin);

    Livewire::test(Dashboard::class)->assertStatus(200);
    Livewire::test(BusinessOverviewStats::class)->assertStatus(200);
    Livewire::test(CollectionsChart::class)->assertStatus(200);
    Livewire::test(DueCustomersWidget::class)->assertStatus(200);
    Livewire::test(PostDatedChequesWidget::class)->assertStatus(200);
});

test('business overview stats widget calculates weekly/monthly stats and dues correctly', function () {
    $this->actingAs($this->admin);

    // Create a payment for this week
    Payment::create([
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => 1,
        'payment_date' => now(),
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 5000.00,
        'received_by' => $this->admin->id,
    ]);

    // Create an invoice for this week
    Invoice::create([
        'invoice_no' => 'INV-001',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => 1,
        'invoice_date' => now(),
        'grand_total' => 8000.00,
        'created_by' => $this->admin->id,
    ]);

    // Create a customer ledger entry to simulate outstanding dues
    CustomerLedger::create([
        'customer_id' => $this->customer->id,
        'transaction_date' => now(),
        'transaction_type' => 'Invoice',
        'debit' => 8000.00,
        'credit' => 5000.00,
        'running_balance' => 3000.00,
    ]);

    // Create a mock SMS log today
    NotificationLog::create([
        'recipient' => '01700998877',
        'notification_type' => 'SMS',
        'payload' => 'Due reminder',
        'delivery_status' => 'logged',
    ]);

    Livewire::test(BusinessOverviewStats::class)
        ->assertSee(__('Weekly Collections'))
        ->assertSee('5,000.00')
        ->assertSee(__('Weekly Sales'))
        ->assertSee('8,000.00')
        ->assertSee(__('Total Outstanding Dues'))
        ->assertSee('3,000.00')
        ->assertSee(__('SMS Sent Today'))
        ->assertSee('1');
});

test('counter boy cannot see SMS log stats in overview stats widget', function () {
    $this->actingAs($this->counterBoy);

    // Create a mock SMS log today
    NotificationLog::create([
        'recipient' => '01700998877',
        'notification_type' => 'SMS',
        'payload' => 'Due reminder',
        'delivery_status' => 'logged',
    ]);

    Livewire::test(BusinessOverviewStats::class)
        ->assertSee(__('Weekly Collections'))
        ->assertDontSee(__('SMS Sent Today'));
});

test('due customers widget table lists only customers with positive balance', function () {
    $this->actingAs($this->admin);

    $noDueCustomer = Customer::create([
        'name' => 'Paid Customer',
        'mobile' => '01888112233',
        'customer_type' => 'Retail',
    ]);

    // Due customer
    CustomerLedger::create([
        'customer_id' => $this->customer->id,
        'transaction_date' => now(),
        'transaction_type' => 'Invoice',
        'debit' => 5000.00,
        'credit' => 0.00,
        'running_balance' => 5000.00,
    ]);

    // Fully paid customer
    CustomerLedger::create([
        'customer_id' => $noDueCustomer->id,
        'transaction_date' => now(),
        'transaction_type' => 'Invoice',
        'debit' => 5000.00,
        'credit' => 5000.00,
        'running_balance' => 0.00,
    ]);

    Livewire::test(DueCustomersWidget::class)
        ->assertCanSeeTableRecords([$this->customer])
        ->assertCanNotSeeTableRecords([$noDueCustomer]);
});

test('can send individual and bulk SMS reminders from due customers widget', function () {
    $this->actingAs($this->admin);

    CustomerLedger::create([
        'customer_id' => $this->customer->id,
        'transaction_date' => now(),
        'transaction_type' => 'Invoice',
        'debit' => 5000.00,
        'credit' => 0.00,
        'running_balance' => 5000.00,
    ]);

    $logPath = storage_path('logs/sendsms.log');
    if (file_exists($logPath)) {
        unlink($logPath);
    }

    // Test individual SMS Action
    Livewire::test(DueCustomersWidget::class)
        ->callTableAction('sendSMS', $this->customer, [
            'message' => 'Custom text message',
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('notification_logs', [
        'recipient' => $this->customer->mobile,
        'notification_type' => 'SMS',
        'payload' => 'Custom text message',
        'delivery_status' => 'logged',
    ]);

    // Test Bulk SMS Action
    Livewire::test(DueCustomersWidget::class)
        ->callTableBulkAction('sendBulkSMS', [$this->customer], [])
        ->assertHasNoTableBulkActionErrors();

    $this->assertDatabaseHas('notification_logs', [
        'recipient' => $this->customer->mobile,
        'notification_type' => 'SMS',
        'delivery_status' => 'logged',
    ]);
});

test('can deposit, clear, and bounce post-dated cheques from widget', function () {
    $this->actingAs($this->admin);

    $session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
        'expected_cash' => 1000.00,
    ]);

    $pdc = PostDatedCheque::create([
        'customer_id' => $this->customer->id,
        'cheque_number' => 'CHQ-123',
        'bank_name' => 'Sonali Bank',
        'amount' => 10000.00,
        'maturity_date' => now()->toDateString(),
        'status' => PdcStatus::Pending,
    ]);

    // 1. Test Deposit Action
    Livewire::test(PostDatedChequesWidget::class)
        ->callTableAction('deposit', $pdc, [
            'deposit_date' => now()->toDateString(),
        ])
        ->assertHasNoTableActionErrors();

    expect($pdc->refresh()->status)->toBe(PdcStatus::Deposited);

    // 2. Test Clear Action (Requires cash register session)
    Livewire::test(PostDatedChequesWidget::class)
        ->callTableAction('clear', $pdc, [
            'cleared_date' => now()->toDateString(),
        ])
        ->assertHasNoTableActionErrors();

    expect($pdc->refresh()->status)->toBe(PdcStatus::Cleared);
    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $session->id,
        'entry_type' => 'ChequeClearance',
        'amount' => 10000.00,
    ]);

    // 3. Test Bounce Action
    $pdc2 = PostDatedCheque::create([
        'customer_id' => $this->customer->id,
        'cheque_number' => 'CHQ-456',
        'bank_name' => 'Sonali Bank',
        'amount' => 12000.00,
        'maturity_date' => now()->toDateString(),
        'status' => PdcStatus::Deposited,
    ]);

    Livewire::test(PostDatedChequesWidget::class)
        ->callTableAction('bounce', $pdc2, [
            'bounce_reason' => 'Insufficient Balance',
        ])
        ->assertHasNoTableActionErrors();

    expect($pdc2->refresh()->status)->toBe(PdcStatus::Bounced);
    $this->assertDatabaseHas('notification_logs', [
        'recipient' => $this->customer->mobile,
        'notification_type' => 'SMS',
        'delivery_status' => 'logged',
    ]);
});
