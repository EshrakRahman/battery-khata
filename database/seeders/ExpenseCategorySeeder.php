<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('7. Creating Expense Categories...');

        $expenseCategoriesData = [
            ['name' => 'Shop Rent'],
            ['name' => 'Electricity Bill'],
            ['name' => 'Tea & Entertainment'],
            ['name' => 'Staff Salary'],
            ['name' => 'Conveyance & Transport'],
            ['name' => 'Stationery & Printing'],
            ['name' => 'Internet & Mobile Bills'],
            ['name' => 'Miscellaneous Outflow'],
        ];

        foreach ($expenseCategoriesData as $ecData) {
            ExpenseCategory::create($ecData);
        }
    }
}
