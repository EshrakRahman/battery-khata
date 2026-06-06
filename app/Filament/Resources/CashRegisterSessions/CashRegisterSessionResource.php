<?php

namespace App\Filament\Resources\CashRegisterSessions;

use App\Filament\Resources\CashRegisterSessions\Pages\CreateCashRegisterSession;
use App\Filament\Resources\CashRegisterSessions\Pages\EditCashRegisterSession;
use App\Filament\Resources\CashRegisterSessions\Pages\ListCashRegisterSessions;
use App\Filament\Resources\CashRegisterSessions\RelationManagers\CashbookEntriesRelationManager;
use App\Filament\Resources\CashRegisterSessions\Schemas\CashRegisterSessionForm;
use App\Filament\Resources\CashRegisterSessions\Tables\CashRegisterSessionsTable;
use App\Models\CashRegisterSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashRegisterSessionResource extends Resource
{
    protected static ?string $model = CashRegisterSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    public static function getNavigationLabel(): string
    {
        return __('Cash Register Sessions');
    }

    public static function getModelLabel(): string
    {
        return __('Cash Register Session');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Cash Register Sessions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Cashbook');
    }

    public static function form(Schema $schema): Schema
    {
        return CashRegisterSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashRegisterSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CashbookEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashRegisterSessions::route('/'),
            'create' => CreateCashRegisterSession::route('/create'),
            'edit' => EditCashRegisterSession::route('/{record}/edit'),
        ];
    }
}
