<?php

namespace App\Filament\Resources\CashRegisterSessions\Pages;

use App\Exceptions\ActiveCashSessionExistsException;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Services\CashSessionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateCashRegisterSession extends CreateRecord
{
    protected static string $resource = CashRegisterSessionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(CashSessionService::class);

        try {
            return $service->openSession(
                user: auth()->user(),
                openingCash: $data['opening_cash'],
                notes: $data['notes'] ?? null
            );
        } catch (ActiveCashSessionExistsException $e) {
            throw ValidationException::withMessages([
                'data.opening_cash' => $e->getMessage(),
            ]);
        }
    }
}
