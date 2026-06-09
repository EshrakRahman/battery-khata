<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the admin login page when accessing the admin panel', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

test('active admin users can access the admin dashboard', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

test('active manager users can access the admin dashboard', function () {
    $manager = User::create([
        'name' => 'Manager User',
        'email' => 'manager@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Manager,
        'is_active' => true,
    ]);

    $this->actingAs($manager)
        ->get('/admin')
        ->assertSuccessful();
});

test('active counter boy users are redirected from the admin dashboard to cash register sessions', function () {
    $counterBoy = User::create([
        'name' => 'Counter Boy User',
        'email' => 'counter@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::CounterBoy,
        'is_active' => true,
    ]);

    $this->actingAs($counterBoy)
        ->get('/admin')
        ->assertRedirect(route('filament.admin.resources.cash-register-sessions.index'));
});

test('inactive users cannot access the admin dashboard', function () {
    $inactiveAdmin = User::create([
        'name' => 'Inactive Admin',
        'email' => 'inactive@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin,
        'is_active' => false,
    ]);

    $this->actingAs($inactiveAdmin)
        ->get('/admin')
        ->assertForbidden();
});
