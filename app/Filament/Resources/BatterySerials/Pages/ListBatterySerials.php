<?php

namespace App\Filament\Resources\BatterySerials\Pages;

use App\Filament\Resources\BatterySerials\BatterySerialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBatterySerials extends ListRecords
{
    protected static string $resource = BatterySerialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
