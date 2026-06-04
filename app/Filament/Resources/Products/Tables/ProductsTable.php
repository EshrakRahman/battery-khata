<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand_name')
                    ->label(__('Brand Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('model_name')
                    ->label(__('Model Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mrp_price')
                    ->label(__('MRP Price'))
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('dealer_price')
                    ->label(__('Dealer Price'))
                    ->money('BDT')
                    ->sortable(),
                IconColumn::make('has_serial_tracking')
                    ->label(__('Serial Tracking'))
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
