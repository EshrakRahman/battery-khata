<?php

namespace App\Filament\Resources\ExpenseCategories;

use App\Enums\UserRole;
use App\Filament\Resources\ExpenseCategories\Pages\CreateExpenseCategory;
use App\Filament\Resources\ExpenseCategories\Pages\EditExpenseCategory;
use App\Filament\Resources\ExpenseCategories\Pages\ListExpenseCategories;
use App\Filament\Resources\ExpenseCategories\Schemas\ExpenseCategoryForm;
use App\Filament\Resources\ExpenseCategories\Tables\ExpenseCategoriesTable;
use App\Models\ExpenseCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== UserRole::CounterBoy;
    }

    public static function getNavigationLabel(): string
    {
        return __('Expense Categories');
    }

    public static function getModelLabel(): string
    {
        return __('Expense Category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Expense Categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Cashbook');
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseCategoriesTable::configure($table);
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
            'index' => ListExpenseCategories::route('/'),
            'create' => CreateExpenseCategory::route('/create'),
            'edit' => EditExpenseCategory::route('/{record}/edit'),
        ];
    }
}
