<?php

namespace Database\Seeders;

use App\Models\Broker;
use Illuminate\Database\Seeder;

class BrokerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('6. Creating Brokers...');

        $brokersData = [
            ['name' => 'Kala Jahangir', 'mobile' => '01712457890', 'commission_rate' => 2.00],
            ['name' => 'Mominul Broker', 'mobile' => '01815678912', 'commission_rate' => 1.50],
            ['name' => 'Sufian Auto Agent', 'mobile' => '01913456789', 'commission_rate' => 2.50],
            ['name' => 'Kabir Commission Broker', 'mobile' => '01671234567', 'commission_rate' => 2.00],
            ['name' => 'Salim Auto Dalal', 'mobile' => '01556789012', 'commission_rate' => 3.00],
        ];

        foreach ($brokersData as $bData) {
            Broker::create($bData);
        }
    }
}
