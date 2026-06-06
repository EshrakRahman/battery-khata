<?php

use App\Enums\UserRole;
use App\Filament\Resources\CashRegisterSessions\Pages\CreateCashRegisterSession;
use App\Filament\Resources\CashRegisterSessions\Pages\EditCashRegisterSession;
use App\Filament\Resources\CashRegisterSessions\Pages\ListCashRegisterSessions;
use App\Models\CashRegisterSession;
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

test('can list cash register sessions', function () {
    $session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
        'expected_cash' => 1000.00,
    ]);

    Livewire::test(ListCashRegisterSessions::class)
        ->assertCanSeeTableRecords([$session])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('creator.name')
        ->assertCanRenderTableColumn('opened_at')
        ->assertCanRenderTableColumn('closed_at')
        ->assertCanRenderTableColumn('opening_cash')
        ->assertCanRenderTableColumn('expected_cash')
        ->assertCanRenderTableColumn('closing_cash')
        ->assertCanRenderTableColumn('shortage_excess');
});

test('can open a cash session', function () {
    Livewire::test(CreateCashRegisterSession::class)
        ->fillForm([
            'opening_cash' => 5000.00,
            'notes' => 'Morning shift',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('cash_register_sessions', [
        'opened_by' => $this->admin->id,
        'opening_cash' => 5000.00,
        'expected_cash' => 5000.00,
        'closed_at' => null,
    ]);
});

test('cannot open a cash session if the user already has an active session', function () {
    // Open one session first
    CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
        'expected_cash' => 1000.00,
    ]);

    // Try to open a second one
    Livewire::test(CreateCashRegisterSession::class)
        ->fillForm([
            'opening_cash' => 2000.00,
        ])
        ->call('create')
        ->assertHasFormErrors(['opening_cash']);
});

test('can close an active cash session with valid denominations', function () {
    $session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
        'expected_cash' => 5000.00,
    ]);

    // To close, we trigger the header action 'close_session'
    Livewire::test(EditCashRegisterSession::class, ['record' => $session->getKey()])
        ->callAction('close_session', [
            'denominations' => [
                '1000' => 5, // 5000 Taka
            ],
            'closing_cash' => 5000.00,
            'notes' => 'Evening closure',
        ])
        ->assertHasNoActionErrors();

    $session->refresh();
    expect($session->closed_at)->not->toBeNull()
        ->and($session->closing_cash)->toEqual(5000.00)
        ->and($session->shortage_excess)->toEqual(0.00)
        ->and($session->denominations)->toBe(['1000' => 5]);
});

test('closing fails if denominations sum does not match closing cash', function () {
    $session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 5000.00,
        'expected_cash' => 5000.00,
    ]);

    // closing_cash is 5000, but denominations total is 4000
    Livewire::test(EditCashRegisterSession::class, ['record' => $session->getKey()])
        ->callAction('close_session', [
            'denominations' => [
                '1000' => 4, // 4000 Taka
            ],
            'closing_cash' => 5000.00,
        ])
        ->assertHasActionErrors(['closing_cash']);
});
