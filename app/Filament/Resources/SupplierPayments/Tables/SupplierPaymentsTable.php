<?php

namespace App\Filament\Resources\SupplierPayments\Tables;

use App\Enums\PaymentMethod;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label(__('Supplier'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label(__('Payment Date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->badge()
                    ->color(fn (PaymentMethod $state): string => match ($state) {
                        PaymentMethod::Cash => 'success',
                        PaymentMethod::Bkash => 'info',
                        PaymentMethod::Nagad => 'warning',
                        PaymentMethod::Rocket => 'warning',
                        PaymentMethod::Bank => 'primary',
                        PaymentMethod::Cheque => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (PaymentMethod $state): string => __($state->value))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('reference_no')
                    ->label(__('Reference Number'))
                    ->searchable(),

                TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label(__('Supplier'))
                    ->relationship('supplier', 'name'),

                SelectFilter::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options(
                        collect(PaymentMethod::cases())
                            ->filter(fn ($method) => $method !== PaymentMethod::ScrapAdjustment)
                            ->mapWithKeys(fn ($method) => [$method->value => __($method->value)])
                            ->toArray()
                    ),
            ])
            ->recordActions([
                ViewAction::make()->slideOver(),
            ])
            ->toolbarActions([]);
    }
}
