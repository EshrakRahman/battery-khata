<?php

use App\Enums\UserRole;
use App\Filament\Resources\Brokers\Pages\CreateBroker;
use App\Filament\Resources\Brokers\Pages\EditBroker;
use App\Filament\Resources\Brokers\Pages\ListBrokers;
use App\Models\Broker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

test('can list brokers', function () {
    $broker = Broker::create([
        'name' => 'Karim Dalal',
        'mobile' => '01711000001',
        'commission_rate' => 2.50,
    ]);

    Livewire::test(ListBrokers::class)
        ->assertCanSeeTableRecords([$broker])
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('mobile')
        ->assertCanRenderTableColumn('commission_rate');
});

test('can create a broker', function () {
    Livewire::test(CreateBroker::class)
        ->fillForm([
            'name' => 'Rahim Dalal',
            'mobile' => '01811000001',
            'commission_rate' => 3.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Broker::where('name', 'Rahim Dalal')->exists())->toBeTrue();
});

test('broker name is required', function () {
    Livewire::test(CreateBroker::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('can edit a broker', function () {
    $broker = Broker::create([
        'name' => 'Old Broker',
        'commission_rate' => 1.00,
    ]);

    Livewire::test(EditBroker::class, ['record' => $broker->getKey()])
        ->fillForm(['commission_rate' => 5.00])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $broker->refresh()->commission_rate)->toBe(5.00);
});
