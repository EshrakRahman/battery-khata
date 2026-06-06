<?php

namespace App\Filament\Resources\CashRegisterSessions\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashbookEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'cashbookEntries';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                TextColumn::make('entry_type')
                    ->label(__('Entry Type'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('direction')
                    ->label(__('Direction'))
                    ->badge()
                    ->color(fn (TransactionDirection $state): string => match ($state) {
                        TransactionDirection::In => 'success',
                        TransactionDirection::Out => 'danger',
                    })
                    ->formatStateUsing(fn (TransactionDirection $state): string => __($state->value)),

                TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->badge()
                    ->color(fn (PaymentMethod $state): string => match ($state) {
                        PaymentMethod::Cash => 'success',
                        PaymentMethod::Bkash => 'info',
                        PaymentMethod::Nagad => 'warning',
                        PaymentMethod::Rocket => 'warning',
                        PaymentMethod::Bank => 'primary',
                        PaymentMethod::Cheque, PaymentMethod::ScrapAdjustment => 'gray',
                    })
                    ->formatStateUsing(fn (PaymentMethod $state): string => __($state->value)),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('reference_type')
                    ->label(__('Reference'))
                    ->formatStateUsing(fn ($record) => $record->reference_type ? (class_basename($record->reference_type).' #'.$record->reference_id) : '-'),

                TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([]);
    }
}
