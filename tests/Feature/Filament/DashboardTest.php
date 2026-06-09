<?php

use App\Enums\PaymentMethod;
use App\Enums\PdcStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\BatterySerials\BatterySerialResource;
use App\Filament\Resources\Brokers\BrokerResource;
use App\Filament\Resources\CashbookEntries\CashbookEntryResource;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\LoanAccounts\LoanAccountResource;
use App\Filament\Resources\NotificationLogs\NotificationLogResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\PostDatedCheques\PostDatedChequeResource;
use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\ScrapCollections\ScrapCollectionResource;
use App\Filament\Resources\ScrapDisposals\ScrapDisposalResource;
use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Filament\Resources\SupplierPayments\SupplierPaymentResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Filament\Resources\WarrantyClaims\WarrantyClaimResource;
use App\Filament\Widgets\BusinessOverviewStats;
use App\Filament\Widgets\CollectionsChart;
use App\Filament\Widgets\DueCustomersWidget;
use App\Filament\Widgets\PostDatedChequesWidget;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\PostDatedCheque;
use App\Models\User;
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

    $session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
        'expected_cash' => 1000.00,
    ]);

    // Create a payment for this week
    Payment::create([
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $session->id,
        'payment_date' => now(),
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 5000.00,
        'received_by' => $this->admin->id,
    ]);

    // Create an invoice for this week
    Invoice::create([
        'invoice_no' => 'INV-001',
        'customer_id' => $this->customer->id,
        'cash_register_session_id' => $session->id,
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
        ->assertSee(__('Invoiced: '))
        ->assertSee('8,000.00')
        ->assertSee(__('Total Outstanding Dues'))
        ->assertSee('3,000.00')
        ->assertSee(__('SMS Sent Today'))
        ->assertSee('1');
});

test('counter boy is redirected from dashboard and it does not show in navigation', function () {
    $this->actingAs($this->counterBoy);

    $this->get('/admin')
        ->assertRedirect(route('filament.admin.resources.cash-register-sessions.index'));

    expect(Dashboard::shouldRegisterNavigation())->toBeFalse();
});

test('admin dashboard registers in navigation', function () {
    $this->actingAs($this->admin);

    expect(Dashboard::shouldRegisterNavigation())->toBeTrue();
});

test('counter boy cannot view dashboard widgets', function () {
    $this->actingAs($this->counterBoy);

    expect(BusinessOverviewStats::canView())->toBeFalse()
        ->and(CollectionsChart::canView())->toBeFalse()
        ->and(DueCustomersWidget::canView())->toBeFalse()
        ->and(PostDatedChequesWidget::canView())->toBeFalse();
});

test('admin and manager can view dashboard widgets', function () {
    $this->actingAs($this->admin);

    expect(BusinessOverviewStats::canView())->toBeTrue()
        ->and(CollectionsChart::canView())->toBeTrue()
        ->and(DueCustomersWidget::canView())->toBeTrue()
        ->and(PostDatedChequesWidget::canView())->toBeTrue();
});

test('counter boy sidebar navigation excludes restricted resources', function () {
    $this->actingAs($this->counterBoy);

    // Resources that should NOT register navigation for Counter Boy
    expect(BatterySerialResource::shouldRegisterNavigation())->toBeFalse()
        ->and(CashbookEntryResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ExpenseCategoryResource::shouldRegisterNavigation())->toBeFalse()
        ->and(LoanAccountResource::shouldRegisterNavigation())->toBeFalse()
        ->and(NotificationLogResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ProductCategoryResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ProductResource::shouldRegisterNavigation())->toBeFalse()
        ->and(PurchaseInvoiceResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ScrapDisposalResource::shouldRegisterNavigation())->toBeFalse()
        ->and(SupplierPaymentResource::shouldRegisterNavigation())->toBeFalse()
        ->and(SupplierResource::shouldRegisterNavigation())->toBeFalse()
        ->and(WarehouseResource::shouldRegisterNavigation())->toBeFalse();

    // Resources that SHOULD register navigation for Counter Boy (e.g. they inherit true by default)
    expect(InvoiceResource::shouldRegisterNavigation())->toBeTrue()
        ->and(PaymentResource::shouldRegisterNavigation())->toBeTrue()
        ->and(CustomerResource::shouldRegisterNavigation())->toBeTrue()
        ->and(BrokerResource::shouldRegisterNavigation())->toBeTrue()
        ->and(CashRegisterSessionResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ScrapCollectionResource::shouldRegisterNavigation())->toBeTrue()
        ->and(StockTransferResource::shouldRegisterNavigation())->toBeTrue()
        ->and(WarrantyClaimResource::shouldRegisterNavigation())->toBeTrue()
        ->and(PostDatedChequeResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ExpenseResource::shouldRegisterNavigation())->toBeTrue();
});

test('admin sidebar navigation includes all resources', function () {
    $this->actingAs($this->admin);

    expect(BatterySerialResource::shouldRegisterNavigation())->toBeTrue()
        ->and(CashbookEntryResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ExpenseCategoryResource::shouldRegisterNavigation())->toBeTrue()
        ->and(LoanAccountResource::shouldRegisterNavigation())->toBeTrue()
        ->and(NotificationLogResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ProductCategoryResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ProductResource::shouldRegisterNavigation())->toBeTrue()
        ->and(PurchaseInvoiceResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ScrapDisposalResource::shouldRegisterNavigation())->toBeTrue()
        ->and(SupplierPaymentResource::shouldRegisterNavigation())->toBeTrue()
        ->and(SupplierResource::shouldRegisterNavigation())->toBeTrue()
        ->and(WarehouseResource::shouldRegisterNavigation())->toBeTrue();
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

test('dashboard grid layout and widget spans are configured correctly', function () {
    $this->actingAs($this->admin);

    $dashboard = new Dashboard;
    expect($dashboard->getColumns())->toBe(['md' => 2]);

    $statsWidget = new BusinessOverviewStats;
    $reflection = new ReflectionClass(BusinessOverviewStats::class);
    $columnSpanProp = $reflection->getProperty('columnSpan');
    $columnSpanProp->setAccessible(true);
    expect($columnSpanProp->getValue($statsWidget))->toBe('full');

    $chartWidget = new CollectionsChart;
    $reflectionChart = new ReflectionClass(CollectionsChart::class);
    $columnSpanChart = $reflectionChart->getProperty('columnSpan');
    $columnSpanChart->setAccessible(true);
    expect($columnSpanChart->getValue($chartWidget))->toBe('full');

    $dueWidget = new DueCustomersWidget;
    $reflectionDue = new ReflectionClass(DueCustomersWidget::class);
    $columnSpanDue = $reflectionDue->getProperty('columnSpan');
    $columnSpanDue->setAccessible(true);
    expect($columnSpanDue->getValue($dueWidget))->toBe(1);

    $pdcWidget = new PostDatedChequesWidget;
    $reflectionPdc = new ReflectionClass(PostDatedChequesWidget::class);
    $columnSpanPdc = $reflectionPdc->getProperty('columnSpan');
    $columnSpanPdc->setAccessible(true);
    expect($columnSpanPdc->getValue($pdcWidget))->toBe(1);
});

test('collections chart supports dynamic filters', function () {
    $this->actingAs($this->admin);

    $chart = new CollectionsChart;
    $reflection = new ReflectionClass(CollectionsChart::class);
    $getFilters = $reflection->getMethod('getFilters');
    $getFilters->setAccessible(true);
    $filters = $getFilters->invoke($chart);

    expect($filters)->toHaveKeys(['7', '30', '90']);

    // Check dynamic heading changes with filter
    $chart->filter = '7';
    expect($chart->getHeading())->toContain('Last 7 Days');

    $chart->filter = '90';
    expect($chart->getHeading())->toContain('Last 90 Days');
});
