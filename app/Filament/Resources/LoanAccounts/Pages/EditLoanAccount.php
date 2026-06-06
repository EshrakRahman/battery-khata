<?php

namespace App\Filament\Resources\LoanAccounts\Pages;

use App\Filament\Resources\LoanAccounts\LoanAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanAccount extends EditRecord
{
    protected static string $resource = LoanAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
