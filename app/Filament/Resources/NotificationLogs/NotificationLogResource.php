<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Enums\UserRole;
use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Filament\Resources\NotificationLogs\Schemas\NotificationLogForm;
use App\Filament\Resources\NotificationLogs\Tables\NotificationLogsTable;
use App\Models\NotificationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== UserRole::CounterBoy;
    }

    public static function getNavigationLabel(): string
    {
        return __('Notification Logs');
    }

    public static function getModelLabel(): string
    {
        return __('Notification Log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Notification Logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Cashbook');
    }

    public static function form(Schema $schema): Schema
    {
        return NotificationLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotificationLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationLogs::route('/'),
        ];
    }
}
