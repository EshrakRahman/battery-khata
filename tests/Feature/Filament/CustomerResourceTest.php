<?php

use App\Enums\UserRole;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\CustomerLedgerRelationManager;
use App\Models\Customer;
use App\Models\CustomerLedger;
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

    $this->actingAs($this->admin);
});

test('can list customers', function () {
    $customer = Customer::create([
        'name' => 'Kashem Driver',
        'mobile' => '01811223344',
        'customer_type' => 'Retail',
    ]);

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('mobile')
        ->assertCanRenderTableColumn('customer_type')
        ->assertCanRenderTableColumn('credit_limit')
        ->assertCanRenderTableColumn('is_active');
});

test('can create a customer', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Rahim Khan',
            'mobile' => '01900000001',
            'customer_type' => 'Dealer',
            'credit_limit' => 50000,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Customer::where('mobile', '01900000001')->exists())->toBeTrue();
});

test('name and mobile are required', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm(['name' => '', 'mobile' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'mobile' => 'required']);
});

test('mobile must be unique', function () {
    Customer::create([
        'name' => 'Existing Customer',
        'mobile' => '01700000001',
        'customer_type' => 'Retail',
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Another Customer',
            'mobile' => '01700000001',
            'customer_type' => 'Retail',
        ])
        ->call('create')
        ->assertHasFormErrors(['mobile' => 'unique']);
});

test('national_id must be unique when provided', function () {
    Customer::create([
        'name' => 'First Customer',
        'mobile' => '01700000002',
        'national_id' => 'NID-999999',
        'customer_type' => 'Retail',
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Second Customer',
            'mobile' => '01800000002',
            'national_id' => 'NID-999999',
            'customer_type' => 'Retail',
        ])
        ->call('create')
        ->assertHasFormErrors(['national_id' => 'unique']);
});

test('can edit a customer', function () {
    $customer = Customer::create([
        'name' => 'Old Name',
        'mobile' => '01700000003',
        'customer_type' => 'Retail',
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->refresh()->name)->toBe('Updated Name');
});

test('customer ledger relation manager shows ledger entries on edit page', function () {
    $customer = Customer::create([
        'name' => 'Ledger Test Customer',
        'mobile' => '01700000099',
        'customer_type' => 'Dealer',
    ]);

    $ledger = CustomerLedger::create([
        'customer_id' => $customer->id,
        'transaction_date' => now(),
        'transaction_type' => 'Invoice',
        'debit' => 15000.00,
        'credit' => 0,
        'running_balance' => 15000.00,
    ]);

    Livewire::test(CustomerLedgerRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->assertCanSeeTableRecords([$ledger])
        ->assertCanRenderTableColumn('transaction_date')
        ->assertCanRenderTableColumn('debit')
        ->assertCanRenderTableColumn('running_balance');
});
