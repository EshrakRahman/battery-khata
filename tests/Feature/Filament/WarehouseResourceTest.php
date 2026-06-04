<?php

use App\Enums\UserRole;
use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Models\User;
use App\Models\Warehouse;
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

test('can list warehouses', function () {
    $warehouse = Warehouse::create([
        'name' => 'Main Showroom',
        'location' => 'Dhaka',
    ]);

    Livewire::test(ListWarehouses::class)
        ->assertCanSeeTableRecords([$warehouse])
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('location');
});

test('can create a warehouse', function () {
    Livewire::test(CreateWarehouse::class)
        ->fillForm([
            'name' => 'Mirpur Godown',
            'location' => 'Mirpur, Dhaka',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Warehouse::where('name', 'Mirpur Godown')->exists())->toBeTrue();
});

test('can edit a warehouse', function () {
    $warehouse = Warehouse::create([
        'name' => 'Old Godown',
        'location' => 'Old Dhaka',
    ]);

    Livewire::test(EditWarehouse::class, [
        'record' => $warehouse->getKey(),
    ])
        ->fillForm([
            'name' => 'New Godown',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($warehouse->refresh()->name)->toBe('New Godown');
});
