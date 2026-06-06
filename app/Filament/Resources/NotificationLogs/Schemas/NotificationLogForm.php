<?php

namespace App\Filament\Resources\NotificationLogs\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NotificationLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('recipient')
                            ->label(__('Recipient'))
                            ->disabled(),
                        TextInput::make('notification_type')
                            ->label(__('Notification Type'))
                            ->disabled(),
                        TextInput::make('delivery_status')
                            ->label(__('Delivery Status'))
                            ->disabled(),
                        TextInput::make('created_at')
                            ->label(__('Created At'))
                            ->disabled(),
                        Textarea::make('payload')
                            ->label(__('Payload'))
                            ->columnSpanFull()
                            ->rows(5)
                            ->disabled(),
                    ]),
            ]);
    }
}
