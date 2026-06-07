<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('5. Creating Suppliers...');

        $suppliersData = [
            ['name' => 'Hamko Battery Ltd', 'mobile' => '01711122233', 'address' => 'Tejgaon I/A, Dhaka', 'supplier_type' => 'Manufacturer'],
            ['name' => 'Rimso Battery Co.', 'mobile' => '01819223344', 'address' => 'Siddhirganj, Narayanganj', 'supplier_type' => 'Manufacturer'],
            ['name' => 'Rahimafrooz Distribution Ltd', 'mobile' => '01911334455', 'address' => 'Nakhalpara, Dhaka', 'supplier_type' => 'Distributor'],
            ['name' => 'Lucas BD Limited', 'mobile' => '01552445566', 'address' => 'Tongi I/A, Gazipur', 'supplier_type' => 'Manufacturer'],
            ['name' => 'Volta Battery BD', 'mobile' => '01675556677', 'address' => 'CEPZ, Chittagong', 'supplier_type' => 'Manufacturer'],
        ];

        foreach ($suppliersData as $sData) {
            Supplier::create($sData);
        }
    }
}
