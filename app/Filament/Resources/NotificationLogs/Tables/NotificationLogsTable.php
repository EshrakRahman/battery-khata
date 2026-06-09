<?php

namespace App\Filament\Resources\NotificationLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                TextColumn::make('recipient')
                    ->label(__('Recipient'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('notification_type')
                    ->label(__('Notification Type'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payload')
                    ->label(__('Payload'))
                    ->searchable()
                    ->limit(50),

                TextColumn::make('delivery_status')
                    ->label(__('Delivery Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'logged', 'sent' => 'success',
                        'mocked' => 'info',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __($state)),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()->slideOver(),
            ])
            ->bulkActions([]);
    }
}
