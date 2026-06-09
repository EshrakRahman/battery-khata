<?php

namespace App\Filament\Resources\ScrapDisposals\Tables;

use App\Enums\PaymentMethod;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScrapDisposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label(__('Factory/Supplier'))
                    ->default('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('disposal_date')
                    ->label(__('Disposal Date'))
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
                        PaymentMethod::ScrapAdjustment => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (PaymentMethod $state): string => __($state->value))
                    ->sortable(),

                TextColumn::make('total_received')
                    ->label(__('Total Received'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('collections_count')
                    ->label(__('Cores Disposed'))
                    ->counts('collections')
                    ->sortable(),

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
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($method) => [$method->value => __($method->value)])->toArray()),
            ])
            ->recordActions([
                ViewAction::make()->slideOver(),
            ])
            ->toolbarActions([]);
    }
}
