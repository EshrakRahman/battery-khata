<?php

namespace App\Filament\Resources\PostDatedCheques\Tables;

use App\Enums\PdcStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostDatedChequesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cheque_number')
                    ->label(__('Cheque Number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bank_name')
                    ->label(__('Bank Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('maturity_date')
                    ->label(__('Maturity Date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (PdcStatus $state): string => match ($state) {
                        PdcStatus::Pending => 'warning',
                        PdcStatus::Deposited => 'info',
                        PdcStatus::Cleared => 'success',
                        PdcStatus::Bounced => 'danger',
                    })
                    ->formatStateUsing(fn (PdcStatus $state): string => __($state->value)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(PdcStatus::cases())->mapWithKeys(fn ($status) => [$status->value => __($status->value)])->toArray()),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }
}
