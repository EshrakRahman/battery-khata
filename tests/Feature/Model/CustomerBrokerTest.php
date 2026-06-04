<?php

use App\Models\Broker;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customers enforce unique mobile and unique national ID', function () {
    Customer::create([
        'name' => 'Abul Kalam',
        'mobile' => '01711122233',
        'national_id' => '1995123456789',
        'division' => 'Dhaka',
        'district' => 'Gazipur',
        'upazila' => 'Joydebpur',
    ]);

    // Test Duplicate Mobile Violation
    try {
        Customer::create([
            'name' => 'Kalam Chowdhury',
            'mobile' => '01711122233', // duplicate phone
            'national_id' => '1995987654321',
        ]);
        $this->fail('Database allowed duplicate customer phone number.');
    } catch (QueryException $e) {
        $this->assertStringContainsString('unique constraint', strtolower($e->getMessage()));
    }

    // Test Duplicate NID Violation
    try {
        Customer::create([
            'name' => 'Abul Hashem',
            'mobile' => '01822233344',
            'national_id' => '1995123456789', // duplicate NID
        ]);
        $this->fail('Database allowed duplicate customer National ID.');
    } catch (QueryException $e) {
        $this->assertStringContainsString('unique constraint', strtolower($e->getMessage()));
    }
});

test('customer profile photo and credit limits casting', function () {
    $customer = Customer::create([
        'name' => 'Elite Dealer Sajjad',
        'mobile' => '01912345678',
        'national_id' => '2000555666777',
        'image_path' => 'avatars/sajjad.jpg',
        'credit_limit' => '150000.50',
    ]);

    expect($customer->image_path)->toBe('avatars/sajjad.jpg');
    expect($customer->credit_limit)->toEqual(150000.50);
});

test('brokers commission rate casting', function () {
    $broker = Broker::create([
        'name' => 'Broker Selim',
        'mobile' => '01566677788',
        'commission_rate' => '2.50',
    ]);

    expect($broker->commission_rate)->toEqual(2.50);
});
