<?php

use App\Enums\UserRole;
use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Models\Supplier;
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

test('can list suppliers', function () {
    $supplier = Supplier::create([
        'name' => 'Hamko Batteries Ltd',
        'mobile' => '01711122233',
        'supplier_type' => 'Manufacturer',
    ]);

    Livewire::test(ListSuppliers::class)
        ->assertCanSeeTableRecords([$supplier])
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('mobile')
        ->assertCanRenderTableColumn('supplier_type');
});

test('can create a supplier', function () {
    Livewire::test(CreateSupplier::class)
        ->fillForm([
            'name' => 'Lucas Industries',
            'mobile' => '01811223344',
            'supplier_type' => 'Regular',
            'address' => '45, Motijheel, Dhaka',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Supplier::where('name', 'Lucas Industries')->exists())->toBeTrue();
});

test('supplier name is required on creation', function () {
    Livewire::test(CreateSupplier::class)
        ->fillForm([
            'name' => '',
            'supplier_type' => 'Regular',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('can edit a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Old Supplier',
        'supplier_type' => 'Regular',
    ]);

    Livewire::test(EditSupplier::class, ['record' => $supplier->getKey()])
        ->fillForm(['name' => 'Updated Supplier'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($supplier->refresh()->name)->toBe('Updated Supplier');
});
