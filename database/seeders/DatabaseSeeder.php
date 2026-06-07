<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            WarehouseSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            SupplierSeeder::class,
            BrokerSeeder::class,
            ExpenseCategorySeeder::class,
            CustomerSeeder::class,
            PurchaseSeeder::class,
            DailyOperationsSeeder::class,
        ]);
    }
}
