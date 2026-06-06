<?php

namespace App\Filament\Resources\CashRegisterSessions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashRegisterSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label(__('Opened By'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('opened_at')
                    ->label(__('Opened At'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('closed_at')
                    ->label(__('Closed At'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('opening_cash')
                    ->label(__('Opening Cash'))
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('expected_cash')
                    ->label(__('Expected Cash'))
                    ->money('BDT')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('closing_cash')
                    ->label(__('Closing Cash'))
                    ->money('BDT')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('shortage_excess')
                    ->label(__('Shortage / Excess'))
                    ->money('BDT')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state < 0 => 'danger',
                        $state > 0 => 'success',
                        default => 'gray',
                    })
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->state(fn ($record): string => $record->closed_at === null ? __('Open') : __('Closed'))
                    ->color(fn ($state): string => $state === __('Open') ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'open' => __('Open'),
                        'closed' => __('Closed'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'open') {
                            return $query->whereNull('closed_at');
                        }
                        if ($data['value'] === 'closed') {
                            return $query->whereNotNull('closed_at');
                        }

                        return $query;
                    }),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }
}
