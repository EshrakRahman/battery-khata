<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        if (auth()->user()?->role === UserRole::CounterBoy) {
            redirect(CashRegisterSessionResource::getUrl('index'));
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== UserRole::CounterBoy;
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
        ];
    }
}
