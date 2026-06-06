<?php

namespace App\Filament\Resources\LoanAccounts;

use App\Filament\Resources\LoanAccounts\Pages\CreateLoanAccount;
use App\Filament\Resources\LoanAccounts\Pages\EditLoanAccount;
use App\Filament\Resources\LoanAccounts\Pages\ListLoanAccounts;
use App\Filament\Resources\LoanAccounts\Schemas\LoanAccountForm;
use App\Filament\Resources\LoanAccounts\Tables\LoanAccountsTable;
use App\Models\LoanAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoanAccountResource extends Resource
{
    protected static ?string $model = LoanAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getNavigationLabel(): string
    {
        return __('Loan Accounts');
    }

    public static function getModelLabel(): string
    {
        return __('Loan Account');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Loan Accounts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Cashbook');
    }

    public static function form(Schema $schema): Schema
    {
        return LoanAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoanAccounts::route('/'),
            'create' => CreateLoanAccount::route('/create'),
            'edit' => EditLoanAccount::route('/{record}/edit'),
        ];
    }
}
