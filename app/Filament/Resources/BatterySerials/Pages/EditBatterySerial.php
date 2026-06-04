<?php

namespace App\Filament\Resources\BatterySerials\Pages;

use App\Filament\Resources\BatterySerials\BatterySerialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBatterySerial extends EditRecord
{
    protected static string $resource = BatterySerialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
