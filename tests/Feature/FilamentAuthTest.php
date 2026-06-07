<?php

use App\Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('login page can render', function () {
    $this->get('/admin/login')
        ->assertSuccessful();
});

test('login page prefills admin credentials by default', function () {
    Livewire::test(Login::class)
        ->assertSet('data.email', 'admin@test.com')
        ->assertSet('data.password', 'password');
});

test('login page can switch credentials via fillCredentials action', function () {
    Livewire::test(Login::class)
        ->call('fillCredentials', 'manager@test.com', 'mypassword')
        ->assertSet('data.email', 'manager@test.com')
        ->assertSet('data.password', 'mypassword');
});
