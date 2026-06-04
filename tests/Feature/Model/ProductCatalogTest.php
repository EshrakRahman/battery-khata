<?php

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a category has many products and a product belongs to a category', function () {
    // Asserting relationship structure
    $category = ProductCategory::create([
        'name' => 'Solar Deep Cycle',
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'brand_name' => 'Hamko',
        'model_name' => 'HPD 120',
        'voltage' => '12V',
        'capacity_ah' => '120Ah',
        'plate_count' => 19,
        'warranty_months' => 18,
        'mrp_price' => 14500.00,
        'dealer_price' => 13000.00,
        'set_price' => 12500.00,
        'standard_set_qty' => 5,
        'purchase_cost' => 11000.00,
        'has_serial_tracking' => true,
        'alert_threshold_qty' => 5,
        'is_active' => true,
    ]);

    expect($category->products)->toHaveCount(1);
    expect($category->products->first()->model_name)->toBe('HPD 120');
    expect($product->category->name)->toBe('Solar Deep Cycle');
});

test('product categories enforce unique names', function () {
    ProductCategory::create(['name' => 'Easy-Bike Series']);

    $this->expectException(QueryException::class);
    ProductCategory::create(['name' => 'Easy-Bike Series']);
});

test('product prices and quantities are cast correctly', function () {
    $category = ProductCategory::create(['name' => 'IPS Batteries']);

    $product = Product::create([
        'category_id' => $category->id,
        'brand_name' => 'Lucas',
        'model_name' => 'LPS 150',
        'mrp_price' => '16500.50',
        'dealer_price' => '15000.75',
        'set_price' => '14200.00',
        'standard_set_qty' => '4',
        'purchase_cost' => '12000.00',
    ]);

    $product->refresh();

    expect($product->mrp_price)->toEqual(16500.50)
        ->and($product->dealer_price)->toEqual(15000.75)
        ->and($product->set_price)->toEqual(14200.00)
        ->and($product->standard_set_qty)->toBe(4)
        ->and($product->has_serial_tracking)->toBe(true) // default value
        ->and($product->is_active)->toBe(true); // default value
});

test('products support soft deletes', function () {
    $category = ProductCategory::create(['name' => 'IPS Batteries']);

    $product = Product::create([
        'category_id' => $category->id,
        'brand_name' => 'Lucas',
        'model_name' => 'LPS 150',
        'mrp_price' => 16500.50,
        'dealer_price' => 15000.75,
    ]);

    $product->delete();

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});
