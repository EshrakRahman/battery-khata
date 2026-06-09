<?php

namespace Database\Seeders;

use App\Models\Customer;
use Faker\Factory;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create();

        $this->command->info('8. Creating Customers...');

        $bdDistricts = [
            ['division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Mirpur', 'address' => 'Mirpur-10, Dhaka'],
            ['division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Uttara', 'address' => 'Sector-4, Uttara, Dhaka'],
            ['division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Badda', 'address' => 'Merul Badda, Dhaka'],
            ['division' => 'Dhaka', 'district' => 'Gazipur', 'upazila' => 'Tongi', 'address' => 'Station Road, Tongi, Gazipur'],
            ['division' => 'Dhaka', 'district' => 'Narayanganj', 'upazila' => 'Sadar', 'address' => 'Chashara, Narayanganj'],
            ['division' => 'Chittagong', 'district' => 'Comilla', 'upazila' => 'Sadar', 'address' => 'Kandirpar, Comilla'],
            ['division' => 'Chittagong', 'district' => 'Feni', 'upazila' => 'Sadar', 'address' => 'Trunk Road, Feni'],
            ['division' => 'Chittagong', 'district' => 'Chittagong', 'upazila' => 'Halishahar', 'address' => 'Halishahar, Chittagong'],
            ['division' => 'Sylhet', 'district' => 'Sylhet', 'upazila' => 'Sadar', 'address' => 'Zindabazar, Sylhet'],
        ];

        $customerNames = [
            'Anisur Rahman', 'Kamal Hossain', 'Jamil Ahmed', 'Farhad Reza', 'Belal Miah',
            'Siddique Battery House', 'Kazi Enterprise', 'Chowdhury Auto Shop', 'Al-Amin Motors',
            'Babul Traders', 'Rahim & Sons', 'Sajib Auto Parts', 'Kamil Hossain', 'Habibullah Store',
            'Ripon Electric', 'Subrata Das', 'Manik Lal', 'Sajal Traders', 'Mizanur Rahman',
            'Bashir & Brothers', 'Alam Auto Center', 'Sumon Auto Electric', 'Polash Battery Center', 'Ziaul Haq',
            'Mamunur Rashid', 'Nazrul Islam', 'Asaduzzaman', 'Tariqul Islam', 'Rony Auto Store',
            'Selim Battery Service', 'Babu Auto Parts', 'Sujon Electric', 'Liton Auto Supply', 'Masud Battery House',
            'Akbar Ali', 'Younus Miah', 'Jahangir Alam', 'Rubel Hossain', 'Shahidul Islam',
            'Dulal Miah', 'Milon Electric', 'Helal Auto Works', 'Rashedul Islam', 'Saiful Islam',
        ];

        // Generate 150 customers
        for ($i = 0; $i < 150; $i++) {
            $isDealer = ($i % 4 === 0); // ~25% Dealers, 75% Retailers
            $name = $customerNames[$i % count($customerNames)];
            if ($isDealer && strpos($name, 'Traders') === false && strpos($name, 'House') === false && strpos($name, 'Enterprise') === false) {
                $name .= ' & Co.';
            }

            $loc = $bdDistricts[$i % count($bdDistricts)];

            Customer::create([
                'name' => $name.' ('.($i + 1).')',
                'mobile' => $faker->randomElement(['017', '018', '019', '016', '015']).$faker->numerify('########'),
                'national_id' => $faker->numerify('#############'),
                'image_path' => null,
                'division' => $loc['division'],
                'district' => $loc['district'],
                'upazila' => $loc['upazila'],
                'address' => $loc['address'],
                'customer_type' => $isDealer ? 'Dealer' : 'Retail',
                'credit_limit' => $isDealer ? $faker->randomElement([100000, 150000, 200000]) : $faker->randomElement([10000, 20000, 30000]),
                'is_active' => true,
            ]);
        }
    }
}
