<?php

use App\Enums\UserRole;
use App\Filament\Resources\LoanAccounts\Pages\ListLoanAccounts;
use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\Broker;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoanAccount;
use App\Models\NotificationLog;
use App\Models\PostDatedCheque;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarrantyClaim;
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
        'name' => 'Counter Boy Mamun',
        'email' => 'mamun@counter.com',
        'password' => bcrypt('password'),
        'role' => UserRole::CounterBoy,
        'is_active' => true,
    ]);
});

test('admin has full access to all model policies', function () {
    $this->actingAs($this->admin);

    // Test some models
    expect($this->admin->can('viewAny', Warehouse::class))->toBeTrue()
        ->and($this->admin->can('create', Warehouse::class))->toBeTrue()
        ->and($this->admin->can('viewAny', LoanAccount::class))->toBeTrue()
        ->and($this->admin->can('create', Invoice::class))->toBeTrue()
        ->and($this->admin->can('delete', Customer::create(['name' => 'Test', 'mobile' => '01711223344'])))->toBeTrue();
});

test('counter boy policy restrictions on catalog, warehouse, and supplier', function () {
    // 1. Warehouse
    expect($this->counterBoy->can('viewAny', Warehouse::class))->toBeTrue()
        ->and($this->counterBoy->can('view', new Warehouse))->toBeTrue()
        ->and($this->counterBoy->can('create', Warehouse::class))->toBeFalse()
        ->and($this->counterBoy->can('update', new Warehouse))->toBeFalse()
        ->and($this->counterBoy->can('delete', new Warehouse))->toBeFalse();

    // 2. Product
    expect($this->counterBoy->can('viewAny', Product::class))->toBeTrue()
        ->and($this->counterBoy->can('create', Product::class))->toBeFalse()
        ->and($this->counterBoy->can('delete', new Product))->toBeFalse();

    // 3. ProductCategory
    expect($this->counterBoy->can('viewAny', ProductCategory::class))->toBeTrue()
        ->and($this->counterBoy->can('create', ProductCategory::class))->toBeFalse()
        ->and($this->counterBoy->can('delete', new ProductCategory))->toBeFalse();

    // 4. Supplier
    expect($this->counterBoy->can('viewAny', Supplier::class))->toBeTrue()
        ->and($this->counterBoy->can('create', Supplier::class))->toBeFalse()
        ->and($this->counterBoy->can('delete', new Supplier))->toBeFalse();

    // 5. PurchaseInvoice
    expect($this->counterBoy->can('viewAny', PurchaseInvoice::class))->toBeTrue()
        ->and($this->counterBoy->can('create', PurchaseInvoice::class))->toBeFalse()
        ->and($this->counterBoy->can('delete', new PurchaseInvoice))->toBeFalse();
});

test('counter boy policy permissions on customer and broker', function () {
    $customer = Customer::create(['name' => 'Kashem', 'mobile' => '01888223344']);
    $broker = Broker::create(['name' => 'Broker Delwar']);

    expect($this->counterBoy->can('viewAny', Customer::class))->toBeTrue()
        ->and($this->counterBoy->can('create', Customer::class))->toBeTrue()
        ->and($this->counterBoy->can('update', $customer))->toBeTrue()
        ->and($this->counterBoy->can('delete', $customer))->toBeFalse();

    expect($this->counterBoy->can('viewAny', Broker::class))->toBeTrue()
        ->and($this->counterBoy->can('create', Broker::class))->toBeTrue()
        ->and($this->counterBoy->can('update', $broker))->toBeTrue()
        ->and($this->counterBoy->can('delete', $broker))->toBeFalse();
});

test('counter boy policy permissions on transactions (invoices, stock transfers, warranties, cheques)', function () {
    $invoice = new Invoice;
    $transfer = new StockTransfer;
    $claim = new WarrantyClaim;
    $cheque = new PostDatedCheque;

    // Allowed viewAny, view, create
    expect($this->counterBoy->can('viewAny', Invoice::class))->toBeTrue()
        ->and($this->counterBoy->can('create', Invoice::class))->toBeTrue()
        ->and($this->counterBoy->can('update', $invoice))->toBeFalse()
        ->and($this->counterBoy->can('delete', $invoice))->toBeFalse();

    expect($this->counterBoy->can('viewAny', StockTransfer::class))->toBeTrue()
        ->and($this->counterBoy->can('create', StockTransfer::class))->toBeTrue()
        ->and($this->counterBoy->can('update', $transfer))->toBeFalse()
        ->and($this->counterBoy->can('delete', $transfer))->toBeFalse();

    // Warranty Claims & PDCs allow update so they can run transitions in pages
    expect($this->counterBoy->can('viewAny', WarrantyClaim::class))->toBeTrue()
        ->and($this->counterBoy->can('create', WarrantyClaim::class))->toBeTrue()
        ->and($this->counterBoy->can('update', $claim))->toBeTrue()
        ->and($this->counterBoy->can('delete', $claim))->toBeFalse();

    expect($this->counterBoy->can('viewAny', PostDatedCheque::class))->toBeTrue()
        ->and($this->counterBoy->can('create', PostDatedCheque::class))->toBeTrue()
        ->and($this->counterBoy->can('update', $cheque))->toBeTrue()
        ->and($this->counterBoy->can('delete', $cheque))->toBeFalse();
});

test('counter boy cannot access loan accounts or notification logs', function () {
    expect($this->counterBoy->can('viewAny', LoanAccount::class))->toBeFalse()
        ->and($this->counterBoy->can('create', LoanAccount::class))->toBeFalse()
        ->and($this->counterBoy->can('viewAny', NotificationLog::class))->toBeFalse();

    // Assert Filament page throws 403 Forbidden
    $this->actingAs($this->counterBoy);

    Livewire::test(ListLoanAccounts::class)->assertStatus(403);
    Livewire::test(ListNotificationLogs::class)->assertStatus(403);
});

test('counter boy can only access own cash register sessions', function () {
    $ownSession = CashRegisterSession::create([
        'opened_by' => $this->counterBoy->id,
        'opened_at' => now(),
        'opening_cash' => 1000,
    ]);

    $otherSession = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 2000,
    ]);

    expect($this->counterBoy->can('viewAny', CashRegisterSession::class))->toBeTrue()
        ->and($this->counterBoy->can('create', CashRegisterSession::class))->toBeTrue()
        ->and($this->counterBoy->can('view', $ownSession))->toBeTrue()
        ->and($this->counterBoy->can('update', $ownSession))->toBeTrue()
        ->and($this->counterBoy->can('view', $otherSession))->toBeFalse()
        ->and($this->counterBoy->can('update', $otherSession))->toBeFalse();
});
