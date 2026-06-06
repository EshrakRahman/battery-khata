<?php

namespace App\Filament\Resources\PostDatedCheques\Pages;

use App\Filament\Resources\PostDatedCheques\PostDatedChequeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostDatedCheques extends ListRecords
{
    protected static string $resource = PostDatedChequeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
