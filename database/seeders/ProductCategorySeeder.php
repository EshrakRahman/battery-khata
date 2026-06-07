<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('3. Creating Product Categories...');

        ProductCategory::create(['name' => 'EasyBike Batteries']);
        ProductCategory::create(['name' => 'IPS Batteries']);
        ProductCategory::create(['name' => 'Solar Batteries']);
        ProductCategory::create(['name' => 'Automotive/Car Batteries']);
        ProductCategory::create(['name' => 'Motorcycle Batteries']);
    }
}
