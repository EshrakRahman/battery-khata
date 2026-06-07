<?php

namespace App\Filament\Resources\ScrapDisposals\Pages;

use App\Filament\Resources\ScrapDisposals\ScrapDisposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScrapDisposals extends ListRecords
{
    protected static string $resource = ScrapDisposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
