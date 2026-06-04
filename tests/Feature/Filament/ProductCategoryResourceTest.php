<?php

use App\Enums\UserRole;
use App\Filament\Resources\ProductCategories\Pages\CreateProductCategory;
use App\Filament\Resources\ProductCategories\Pages\EditProductCategory;
use App\Filament\Resources\ProductCategories\Pages\ListProductCategories;
use App\Models\ProductCategory;
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

test('can list product categories', function () {
    $category = ProductCategory::create(['name' => 'Lead Acid Batteries']);

    Livewire::test(ListProductCategories::class)
        ->assertCanSeeTableRecords([$category])
        ->assertCanRenderTableColumn('name');
});

test('can create a product category', function () {
    Livewire::test(CreateProductCategory::class)
        ->fillForm([
            'name' => 'IPS Batteries',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ProductCategory::where('name', 'IPS Batteries')->exists())->toBeTrue();
});

test('unique category validation', function () {
    ProductCategory::create(['name' => 'Lead Acid Batteries']);

    Livewire::test(CreateProductCategory::class)
        ->fillForm([
            'name' => 'Lead Acid Batteries',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

test('can edit a product category', function () {
    $category = ProductCategory::create(['name' => 'Old Category']);

    Livewire::test(EditProductCategory::class, [
        'record' => $category->getKey(),
    ])
        ->fillForm([
            'name' => 'Updated Category',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe('Updated Category');
});
