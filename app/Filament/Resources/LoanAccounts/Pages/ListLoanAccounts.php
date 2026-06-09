<?php

namespace App\Filament\Resources\LoanAccounts\Pages;

use App\Filament\Resources\LoanAccounts\LoanAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanAccounts extends ListRecords
{
    protected static string $resource = LoanAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
