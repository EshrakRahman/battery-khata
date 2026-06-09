<?php

namespace App\Filament\Resources\ScrapDisposals;

use App\Enums\UserRole;
use App\Filament\Resources\ScrapDisposals\Pages\CreateScrapDisposal;
use App\Filament\Resources\ScrapDisposals\Pages\ListScrapDisposals;
use App\Filament\Resources\ScrapDisposals\Schemas\ScrapDisposalForm;
use App\Filament\Resources\ScrapDisposals\Tables\ScrapDisposalsTable;
use App\Models\ScrapDisposal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScrapDisposalResource extends Resource
{
    protected static ?string $model = ScrapDisposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== UserRole::CounterBoy;
    }

    public static function getNavigationLabel(): string
    {
        return __('Scrap Disposals');
    }

    public static function getModelLabel(): string
    {
        return __('Scrap Disposal');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Scrap Disposals');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function form(Schema $schema): Schema
    {
        return ScrapDisposalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScrapDisposalsTable::configure($table);
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
            'index' => ListScrapDisposals::route('/'),
            'create' => CreateScrapDisposal::route('/create'),
        ];
    }
}
