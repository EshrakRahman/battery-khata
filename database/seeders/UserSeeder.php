<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('1. Creating Users...');

        User::create([
            'name' => 'Alhaj Abul Kalam',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Zubair Rahman',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Faisal Ahmed',
            'email' => 'manager2@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $boyNames = ['Kabir Hossain', 'Sajid Miah', 'Rony Islam', 'Arifur Rahman', 'Imran Khan'];
        foreach ($boyNames as $index => $name) {
            User::create([
                'name' => $name,
                'email' => 'counterboy'.($index + 1).'@test.com',
                'password' => Hash::make('password'),
                'role' => UserRole::CounterBoy,
                'is_active' => true,
            ]);
        }

        // For login backward compatibility
        User::create([
            'name' => 'Default Counter Boy',
            'email' => 'counterboy@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::CounterBoy,
            'is_active' => true,
        ]);
    }
}
