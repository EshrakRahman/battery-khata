<?php

namespace App\Filament\Resources\ScrapCollections\Pages;

use App\Filament\Resources\ScrapCollections\ScrapCollectionResource;
use Filament\Resources\Pages\ListRecords;

class ListScrapCollections extends ListRecords
{
    protected static string $resource = ScrapCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
