<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('default locale is English', function () {
    expect(app()->getLocale())->toBe('en');
});

test('session locale variable is respected and sets app locale to Bangla', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->withSession(['locale' => 'bn'])
        ->get('/admin');

    expect(app()->getLocale())->toBe('bn');
});

test('query parameter locale is respected and sets app locale to Bangla', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin?locale=bn');

    expect(app()->getLocale())->toBe('bn');
});

test('translations work in Bangla locale', function () {
    $this->withSession(['locale' => 'bn'])
        ->get('/admin/login')
        ->assertSee('সাইন ইন')
        ->assertSee('মনে রাখুন');
});
