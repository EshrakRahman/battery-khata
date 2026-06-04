<?php

namespace App\Filament\Resources\BatterySerials;

use App\Filament\Resources\BatterySerials\Pages\CreateBatterySerial;
use App\Filament\Resources\BatterySerials\Pages\EditBatterySerial;
use App\Filament\Resources\BatterySerials\Pages\ListBatterySerials;
use App\Filament\Resources\BatterySerials\Schemas\BatterySerialForm;
use App\Filament\Resources\BatterySerials\Tables\BatterySerialsTable;
use App\Models\BatterySerial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BatterySerialResource extends Resource
{
    protected static ?string $model = BatterySerial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return __('Battery Serials');
    }

    public static function getModelLabel(): string
    {
        return __('Battery Serial');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Battery Serials');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog');
    }

    public static function form(Schema $schema): Schema
    {
        return BatterySerialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BatterySerialsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatterySerials::route('/'),
            'create' => CreateBatterySerial::route('/create'),
            'edit' => EditBatterySerial::route('/{record}/edit'),
        ];
    }
}
