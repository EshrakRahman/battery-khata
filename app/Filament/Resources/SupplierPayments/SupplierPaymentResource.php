<?php

namespace App\Filament\Resources\SupplierPayments;

use App\Enums\UserRole;
use App\Filament\Resources\SupplierPayments\Pages\CreateSupplierPayment;
use App\Filament\Resources\SupplierPayments\Pages\ListSupplierPayments;
use App\Filament\Resources\SupplierPayments\Schemas\SupplierPaymentForm;
use App\Filament\Resources\SupplierPayments\Tables\SupplierPaymentsTable;
use App\Models\SupplierPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = SupplierPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== UserRole::CounterBoy;
    }

    public static function getNavigationLabel(): string
    {
        return __('Supplier Payments');
    }

    public static function getModelLabel(): string
    {
        return __('Supplier Payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Supplier Payments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Purchases');
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierPaymentsTable::configure($table);
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
            'index' => ListSupplierPayments::route('/'),
            'create' => CreateSupplierPayment::route('/create'),
        ];
    }
}
