<?php

namespace App\Filament\Resources\BatterySerials\Tables;

use App\Enums\BatteryStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BatterySerialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_no')
                    ->label(__('Serial Number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.brand_name')
                    ->label(__('Brand'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.model_name')
                    ->label(__('Model'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof BatteryStatus ? $state->value : $state) {
                        'in_stock' => 'success',
                        'sold' => 'gray',
                        'reserved' => 'warning',
                        'warranty_claim' => 'danger',
                        'buffer_issued' => 'info',
                        'supplier_returned' => 'danger',
                        'scrap' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state instanceof BatteryStatus ? $state->value : $state) {
                        'in_stock' => __('In Stock'),
                        'sold' => __('Sold'),
                        'reserved' => __('Reserved'),
                        'warranty_claim' => __('Warranty Claim'),
                        'buffer_issued' => __('Buffer Issued'),
                        'supplier_returned' => __('Supplier Returned'),
                        'scrap' => __('Scrap'),
                        default => (string) $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
