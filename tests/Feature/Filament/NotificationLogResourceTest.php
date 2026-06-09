<?php

use App\Enums\UserRole;
use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\NotificationLog;
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

test('can list notification logs', function () {
    $log = NotificationLog::create([
        'recipient' => '01711223344',
        'notification_type' => 'SMS',
        'payload' => 'Hello Mamun',
        'delivery_status' => 'logged',
    ]);

    Livewire::test(ListNotificationLogs::class)
        ->assertCanSeeTableRecords([$log])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('recipient')
        ->assertCanRenderTableColumn('notification_type')
        ->assertCanRenderTableColumn('payload')
        ->assertCanRenderTableColumn('delivery_status')
        ->assertCanRenderTableColumn('created_at');
});
