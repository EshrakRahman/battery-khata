<?php

namespace App\Filament\Resources\CashbookEntries\Pages;

use App\Filament\Resources\CashbookEntries\CashbookEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashbookEntries extends ListRecords
{
    protected static string $resource = CashbookEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
