<?php

use App\Enums\PaymentMethod;
use App\Enums\PdcStatus;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\PostDatedCheques\Pages\CreatePostDatedCheque;
use App\Filament\Resources\PostDatedCheques\Pages\EditPostDatedCheque;
use App\Filament\Resources\PostDatedCheques\Pages\ListPostDatedCheques;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\PostDatedCheque;
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

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
        'expected_cash' => 1000.00,
    ]);

    $this->customer = Customer::create([
        'name' => 'Kashem Driver',
        'mobile' => '01700998877',
        'customer_type' => 'Retail',
    ]);
});

test('can list post dated cheques', function () {
    $pdc = PostDatedCheque::create([
        'customer_id' => $this->customer->id,
        'cheque_number' => 'CHQ-12345',
        'bank_name' => 'Sonali Bank',
        'amount' => 5000.00,
        'maturity_date' => now()->addDays(7)->toDateString(),
        'status' => PdcStatus::Pending,
    ]);

    Livewire::test(ListPostDatedCheques::class)
        ->assertCanSeeTableRecords([$pdc])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('customer.name')
        ->assertCanRenderTableColumn('cheque_number')
        ->assertCanRenderTableColumn('bank_name')
        ->assertCanRenderTableColumn('amount')
        ->assertCanRenderTableColumn('maturity_date')
        ->assertCanRenderTableColumn('status');
});

test('can create a post dated cheque', function () {
    Livewire::test(CreatePostDatedCheque::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'cheque_number' => 'CHQ-98765',
            'bank_name' => 'Janata Bank',
            'amount' => 12500.00,
            'maturity_date' => now()->addDays(14)->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('post_dated_cheques', [
        'cheque_number' => 'CHQ-98765',
        'amount' => 12500.00,
        'status' => PdcStatus::Pending->value,
    ]);
});

test('can transition cheque from pending to deposited', function () {
    $pdc = PostDatedCheque::create([
        'customer_id' => $this->customer->id,
        'cheque_number' => 'CHQ-12345',
        'bank_name' => 'Sonali Bank',
        'amount' => 5000.00,
        'maturity_date' => now()->toDateString(),
        'status' => PdcStatus::Pending,
    ]);

    Livewire::test(EditPostDatedCheque::class, ['record' => $pdc->getKey()])
        ->callAction('deposit', [
            'deposit_date' => now()->toDateString(),
        ])
        ->assertHasNoActionErrors();

    $pdc->refresh();
    expect($pdc->status)->toBe(PdcStatus::Deposited)
        ->and($pdc->deposit_date->toDateString())->toBe(now()->toDateString());
});

test('can transition cheque from deposited to cleared', function () {
    $pdc = PostDatedCheque::create([
        'customer_id' => $this->customer->id,
        'cheque_number' => 'CHQ-12345',
        'bank_name' => 'Sonali Bank',
        'amount' => 5000.00,
        'maturity_date' => now()->toDateString(),
        'status' => PdcStatus::Deposited,
        'deposit_date' => now()->toDateString(),
    ]);

    Livewire::test(EditPostDatedCheque::class, ['record' => $pdc->getKey()])
        ->callAction('clear', [
            'cleared_date' => now()->toDateString(),
        ])
        ->assertHasNoActionErrors();

    $pdc->refresh();
    expect($pdc->status)->toBe(PdcStatus::Cleared)
        ->and($pdc->cleared_date->toDateString())->toBe(now()->toDateString());

    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $this->session->id,
        'entry_type' => 'ChequeClearance',
        'direction' => TransactionDirection::In->value,
        'payment_method' => PaymentMethod::Bank->value,
        'amount' => 5000.00,
        'reference_type' => PostDatedCheque::class,
        'reference_id' => $pdc->id,
    ]);
});

test('can transition cheque from deposited to bounced and dispatch SMS notification', function () {
    $pdc = PostDatedCheque::create([
        'customer_id' => $this->customer->id,
        'cheque_number' => 'CHQ-12345',
        'bank_name' => 'Sonali Bank',
        'amount' => 5000.00,
        'maturity_date' => now()->toDateString(),
        'status' => PdcStatus::Deposited,
        'deposit_date' => now()->toDateString(),
    ]);

    $logPath = storage_path('logs/sendsms.log');
    if (file_exists($logPath)) {
        unlink($logPath);
    }

    Livewire::test(EditPostDatedCheque::class, ['record' => $pdc->getKey()])
        ->callAction('bounce', [
            'bounce_reason' => 'Insufficient funds',
        ])
        ->assertHasNoActionErrors();

    $pdc->refresh();
    expect($pdc->status)->toBe(PdcStatus::Bounced)
        ->and($pdc->bounce_reason)->toBe('Insufficient funds');

    // Assert database notification log exists
    $this->assertDatabaseHas('notification_logs', [
        'recipient' => '01700998877',
        'notification_type' => 'SMS',
        'delivery_status' => 'logged',
    ]);

    // Assert sendsms.log file exists and is populated
    expect(file_exists($logPath))->toBeTrue();
    $logContent = file_get_contents($logPath);
    expect($logContent)->toContain('To: 01700998877')
        ->and($logContent)->toContain('Insufficient funds')
        ->and($logContent)->toContain('CHQ-12345')
        ->and($logContent)->toContain('5,000');
});

test('cheque creation validates required fields', function () {
    Livewire::test(CreatePostDatedCheque::class)
        ->fillForm([
            'customer_id' => '',
            'cheque_number' => '',
            'bank_name' => '',
            'amount' => '',
            'maturity_date' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'customer_id' => 'required',
            'cheque_number' => 'required',
            'bank_name' => 'required',
            'amount' => 'required',
            'maturity_date' => 'required',
        ]);
});

test('bouncing a cheque validates bounce_reason', function () {
    $pdc = PostDatedCheque::create([
        'customer_id' => $this->customer->id,
        'cheque_number' => 'CHQ-12345',
        'bank_name' => 'Sonali Bank',
        'amount' => 5000.00,
        'maturity_date' => now()->toDateString(),
        'status' => PdcStatus::Deposited,
        'deposit_date' => now()->toDateString(),
    ]);

    Livewire::test(EditPostDatedCheque::class, ['record' => $pdc->getKey()])
        ->callAction('bounce', [
            'bounce_reason' => '',
        ])
        ->assertHasActionErrors(['bounce_reason' => 'required']);
});
