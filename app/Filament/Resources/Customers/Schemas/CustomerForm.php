<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Personal Info'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('mobile')
                            ->label(__('Mobile'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('national_id')
                            ->label(__('NID / National ID'))
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Select::make('customer_type')
                            ->label(__('Customer Type'))
                            ->options([
                                'Retail' => __('Retail'),
                                'Dealer' => __('Dealer'),
                                'Garage' => __('Garage'),
                            ])
                            ->default('Retail')
                            ->required(),
                        FileUpload::make('image_path')
                            ->label(__('Identity Photo'))
                            ->image()
                            ->disk('public')
                            ->directory('customers')
                            ->imagePreviewHeight('120')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('Is Active'))
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Regional Address'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('division')
                            ->label(__('Division'))
                            ->maxLength(100),
                        TextInput::make('district')
                            ->label(__('District'))
                            ->maxLength(100),
                        TextInput::make('upazila')
                            ->label(__('Upazila'))
                            ->maxLength(100),
                        Textarea::make('address')
                            ->label(__('Address'))
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Credit Settings'))
                    ->schema([
                        TextInput::make('credit_limit')
                            ->label(__('Credit Limit'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('৳'),
                    ]),
            ]);
    }
}
