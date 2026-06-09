<?php

namespace App\Filament\Resources\ScrapCollections;

use App\Filament\Resources\ScrapCollections\Pages\ListScrapCollections;
use App\Filament\Resources\ScrapCollections\Tables\ScrapCollectionsTable;
use App\Models\ScrapCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScrapCollectionResource extends Resource
{
    protected static ?string $model = ScrapCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function getNavigationLabel(): string
    {
        return __('Scrap Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Scrap Core');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Scrap Cores');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ScrapCollectionsTable::configure($table);
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
            'index' => ListScrapCollections::route('/'),
        ];
    }
}
