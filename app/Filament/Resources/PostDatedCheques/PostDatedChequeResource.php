<?php

namespace App\Filament\Resources\PostDatedCheques;

use App\Filament\Resources\PostDatedCheques\Pages\CreatePostDatedCheque;
use App\Filament\Resources\PostDatedCheques\Pages\EditPostDatedCheque;
use App\Filament\Resources\PostDatedCheques\Pages\ListPostDatedCheques;
use App\Filament\Resources\PostDatedCheques\Schemas\PostDatedChequeForm;
use App\Filament\Resources\PostDatedCheques\Tables\PostDatedChequesTable;
use App\Models\PostDatedCheque;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PostDatedChequeResource extends Resource
{
    protected static ?string $model = PostDatedCheque::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getNavigationLabel(): string
    {
        return __('Cheques');
    }

    public static function getModelLabel(): string
    {
        return __('Cheque');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Cheques');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Cashbook');
    }

    public static function form(Schema $schema): Schema
    {
        return PostDatedChequeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostDatedChequesTable::configure($table);
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
            'index' => ListPostDatedCheques::route('/'),
            'create' => CreatePostDatedCheque::route('/create'),
            'edit' => EditPostDatedCheque::route('/{record}/edit'),
        ];
    }
}
