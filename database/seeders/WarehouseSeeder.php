<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('2. Creating Warehouses...');

        Warehouse::create([
            'name' => 'Main Showroom',
            'location' => 'Station Road, Tongi, Gazipur',
        ]);

        Warehouse::create([
            'name' => 'West Godown Store',
            'location' => 'West Arichpur, Tongi, Gazipur',
        ]);

        Warehouse::create([
            'name' => 'Scrap Storage Room',
            'location' => 'Tongi Bazar, Gazipur',
        ]);
    }
}
