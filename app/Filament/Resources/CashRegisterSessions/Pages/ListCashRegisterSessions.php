<?php

namespace App\Filament\Resources\CashRegisterSessions\Pages;

use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Models\CashRegisterSession;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashRegisterSessions extends ListRecords
{
    protected static string $resource = CashRegisterSessionResource::class;

    protected function getHeaderActions(): array
    {
        $hasActiveSession = CashRegisterSession::query()
            ->where('opened_by', auth()->id())
            ->whereNull('closed_at')
            ->exists();

        return [
            CreateAction::make()
                ->label(__('Open Session'))
                ->disabled($hasActiveSession)
                ->tooltip($hasActiveSession ? __('You already have an active session.') : null),
        ];
    }
}
