<?php

namespace App\Filament\Resources\CashbookEntries;

use App\Filament\Resources\CashbookEntries\Pages\ListCashbookEntries;
use App\Filament\Resources\CashbookEntries\Schemas\CashbookEntryForm;
use App\Filament\Resources\CashbookEntries\Tables\CashbookEntriesTable;
use App\Models\CashbookEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashbookEntryResource extends Resource
{
    protected static ?string $model = CashbookEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    public static function getNavigationLabel(): string
    {
        return __('Cashbook Entries');
    }

    public static function getModelLabel(): string
    {
        return __('Cashbook Entry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Cashbook Entries');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Cashbook');
    }

    public static function form(Schema $schema): Schema
    {
        return CashbookEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashbookEntriesTable::configure($table);
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
            'index' => ListCashbookEntries::route('/'),
        ];
    }
}
