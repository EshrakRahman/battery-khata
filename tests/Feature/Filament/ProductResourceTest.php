<?php

use App\Enums\UserRole;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
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

    $this->category = ProductCategory::create(['name' => 'Lead Acid Batteries']);

    $this->actingAs($this->admin);
});

test('can list products', function () {
    $product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Volvo',
        'model_name' => 'V-120',
        'mrp_price' => 12000.00,
        'dealer_price' => 10500.00,
    ]);

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$product])
        ->assertCanRenderTableColumn('brand_name')
        ->assertCanRenderTableColumn('model_name')
        ->assertCanRenderTableColumn('category.name');
});

test('can create a product', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $this->category->id,
            'brand_name' => 'Lucas',
            'model_name' => 'L-150',
            'voltage' => '12V',
            'capacity_ah' => '150Ah',
            'plate_count' => 21,
            'warranty_months' => 18,
            'purchase_cost' => 8500.00,
            'mrp_price' => 11000.00,
            'dealer_price' => 9500.00,
            'set_price' => 9200.00,
            'standard_set_qty' => 4,
            'alert_threshold_qty' => 5,
            'has_serial_tracking' => true,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::where('brand_name', 'Lucas')->exists())->toBeTrue();
});

test('required fields validation', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors([
            'category_id' => 'required',
            'brand_name' => 'required',
            'model_name' => 'required',
            'mrp_price' => 'required',
            'dealer_price' => 'required',
        ]);
});

test('can edit a product', function () {
    $product = Product::create([
        'category_id' => $this->category->id,
        'brand_name' => 'Old Brand',
        'model_name' => 'Old Model',
        'mrp_price' => 10000.00,
        'dealer_price' => 9000.00,
    ]);

    Livewire::test(EditProduct::class, [
        'record' => $product->getKey(),
    ])
        ->fillForm([
            'brand_name' => 'New Brand',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh()->brand_name)->toBe('New Brand');
});
