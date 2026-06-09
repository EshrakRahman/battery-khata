<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('4. Creating Products...');

        $catEasybike = ProductCategory::where('name', 'EasyBike Batteries')->first();
        $catIps = ProductCategory::where('name', 'IPS Batteries')->first();
        $catSolar = ProductCategory::where('name', 'Solar Batteries')->first();
        $catAutomotive = ProductCategory::where('name', 'Automotive/Car Batteries')->first();
        $catMotorcycle = ProductCategory::where('name', 'Motorcycle Batteries')->first();

        $productsData = [
            // Category: EasyBike
            ['category_id' => $catEasybike->id, 'brand_name' => 'Rimso', 'model_name' => 'Easy-100', 'mrp_price' => 11000.00, 'dealer_price' => 9500.00, 'purchase_cost' => 8000.00, 'warranty_months' => 12, 'standard_set_qty' => 4, 'set_price' => 37000.00],
            ['category_id' => $catEasybike->id, 'brand_name' => 'Hamko', 'model_name' => 'H-Easy 120', 'mrp_price' => 13500.00, 'dealer_price' => 12000.00, 'purchase_cost' => 10200.00, 'warranty_months' => 12, 'standard_set_qty' => 4, 'set_price' => 47000.00],
            ['category_id' => $catEasybike->id, 'brand_name' => 'Volta', 'model_name' => 'V-Easy 130', 'mrp_price' => 14000.00, 'dealer_price' => 12500.00, 'purchase_cost' => 10800.00, 'warranty_months' => 12, 'standard_set_qty' => 4, 'set_price' => 49000.00],

            // Category: IPS
            ['category_id' => $catIps->id, 'brand_name' => 'Rahimafrooz', 'model_name' => 'IPB-150', 'mrp_price' => 18500.00, 'dealer_price' => 16500.00, 'purchase_cost' => 14200.00, 'warranty_months' => 18, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catIps->id, 'brand_name' => 'Hamko', 'model_name' => 'HPD-150', 'mrp_price' => 17000.00, 'dealer_price' => 15000.00, 'purchase_cost' => 13000.00, 'warranty_months' => 18, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catIps->id, 'brand_name' => 'Rimso', 'model_name' => 'Tubular-200', 'mrp_price' => 22000.00, 'dealer_price' => 19500.00, 'purchase_cost' => 17000.00, 'warranty_months' => 24, 'standard_set_qty' => 1, 'set_price' => null],

            // Category: Solar
            ['category_id' => $catSolar->id, 'brand_name' => 'Hamko', 'model_name' => 'Solar-80', 'mrp_price' => 9500.00, 'dealer_price' => 8200.00, 'purchase_cost' => 7000.00, 'warranty_months' => 36, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catSolar->id, 'brand_name' => 'Rahimafrooz', 'model_name' => 'S-100', 'mrp_price' => 12000.00, 'dealer_price' => 10500.00, 'purchase_cost' => 9000.00, 'warranty_months' => 36, 'standard_set_qty' => 1, 'set_price' => null],

            // Category: Automotive
            ['category_id' => $catAutomotive->id, 'brand_name' => 'Lucas', 'model_name' => 'L-12V-70', 'mrp_price' => 9500.00, 'dealer_price' => 8500.00, 'purchase_cost' => 7200.00, 'warranty_months' => 18, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catAutomotive->id, 'brand_name' => 'Lucas', 'model_name' => 'L-12V-100', 'mrp_price' => 13000.00, 'dealer_price' => 11500.00, 'purchase_cost' => 9800.00, 'warranty_months' => 18, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catAutomotive->id, 'brand_name' => 'Volvo', 'model_name' => 'V-80', 'mrp_price' => 8800.00, 'dealer_price' => 7800.00, 'purchase_cost' => 6500.00, 'warranty_months' => 12, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catAutomotive->id, 'brand_name' => 'Volvo', 'model_name' => 'V-100', 'mrp_price' => 11500.00, 'dealer_price' => 10200.00, 'purchase_cost' => 8600.00, 'warranty_months' => 12, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catAutomotive->id, 'brand_name' => 'Hamko', 'model_name' => 'H-80', 'mrp_price' => 9000.00, 'dealer_price' => 8000.00, 'purchase_cost' => 6800.00, 'warranty_months' => 18, 'standard_set_qty' => 1, 'set_price' => null],

            // Category: Motorcycle
            ['category_id' => $catMotorcycle->id, 'brand_name' => 'Volta', 'model_name' => 'V-12V-5Ah', 'mrp_price' => 1600.00, 'dealer_price' => 1350.00, 'purchase_cost' => 1100.00, 'warranty_months' => 6, 'standard_set_qty' => 1, 'set_price' => null],
            ['category_id' => $catMotorcycle->id, 'brand_name' => 'Hamko', 'model_name' => 'H-12V-7Ah', 'mrp_price' => 1950.00, 'dealer_price' => 1650.00, 'purchase_cost' => 1350.00, 'warranty_months' => 6, 'standard_set_qty' => 1, 'set_price' => null],
        ];

        foreach ($productsData as $pData) {
            Product::create(array_merge($pData, [
                'alert_threshold_qty' => 6,
                'has_serial_tracking' => true,
                'is_active' => true,
            ]));
        }
    }
}
