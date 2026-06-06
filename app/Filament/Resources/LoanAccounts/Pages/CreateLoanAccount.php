<?php

namespace App\Filament\Resources\LoanAccounts\Pages;

use App\Filament\Resources\LoanAccounts\LoanAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanAccount extends CreateRecord
{
    protected static string $resource = LoanAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['outstanding_balance'] = $data['principal_amount'];

        return $data;
    }
}
