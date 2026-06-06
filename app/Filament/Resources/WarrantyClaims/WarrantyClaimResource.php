<?php

namespace App\Filament\Resources\WarrantyClaims;

use App\Filament\Resources\WarrantyClaims\Pages\CreateWarrantyClaim;
use App\Filament\Resources\WarrantyClaims\Pages\EditWarrantyClaim;
use App\Filament\Resources\WarrantyClaims\Pages\ListWarrantyClaims;
use App\Filament\Resources\WarrantyClaims\Schemas\WarrantyClaimForm;
use App\Filament\Resources\WarrantyClaims\Tables\WarrantyClaimsTable;
use App\Models\WarrantyClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarrantyClaimResource extends Resource
{
    protected static ?string $model = WarrantyClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getNavigationLabel(): string
    {
        return __('Warranty Claims');
    }

    public static function getModelLabel(): string
    {
        return __('Warranty Claim');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Warranty Claims');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function form(Schema $schema): Schema
    {
        return WarrantyClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarrantyClaimsTable::configure($table);
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
            'index' => ListWarrantyClaims::route('/'),
            'create' => CreateWarrantyClaim::route('/create'),
            'edit' => EditWarrantyClaim::route('/{record}/edit'),
        ];
    }
}
