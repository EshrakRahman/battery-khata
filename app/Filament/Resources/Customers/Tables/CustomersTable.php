<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mobile')
                    ->label(__('Mobile'))
                    ->searchable(),
                TextColumn::make('national_id')
                    ->label(__('NID / National ID'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('customer_type')
                    ->label(__('Customer Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dealer' => 'info',
                        'Garage' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('credit_limit')
                    ->label(__('Credit Limit'))
                    ->money('BDT')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Is Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->label(__('Customer Type'))
                    ->options([
                        'Retail' => __('Retail'),
                        'Dealer' => __('Dealer'),
                        'Garage' => __('Garage'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
