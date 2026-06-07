<?php

namespace App\Filament\Resources\PostDatedCheques\Pages;

use App\Enums\PaymentMethod;
use App\Enums\PdcStatus;
use App\Enums\TransactionDirection;
use App\Filament\Resources\PostDatedCheques\PostDatedChequeResource;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\PostDatedCheque;
use App\Services\SmsService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditPostDatedCheque extends EditRecord
{
    protected static string $resource = PostDatedChequeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deposit')
                ->label(__('Deposit'))
                ->color('info')
                ->visible(fn ($record) => $record && $record->status === PdcStatus::Pending)
                ->form([
                    DatePicker::make('deposit_date')
                        ->label(__('Deposit Date'))
                        ->required()
                        ->default(now()),
                ])
                ->action(function ($record, array $data) {
                    $record->update([
                        'status' => PdcStatus::Deposited,
                        'deposit_date' => $data['deposit_date'],
                    ]);

                    Notification::make()
                        ->title(__('Cheque Deposited'))
                        ->success()
                        ->send();
                }),

            Action::make('clear')
                ->label(__('Clear'))
                ->color('success')
                ->visible(fn ($record) => $record && $record->status === PdcStatus::Deposited)
                ->form([
                    DatePicker::make('cleared_date')
                        ->label(__('Cleared Date'))
                        ->required()
                        ->default(now()),
                ])
                ->action(function ($record, array $data) {
                    $user = auth()->user();
                    $activeSession = CashRegisterSession::where('opened_by', $user->id)
                        ->whereNull('closed_at')
                        ->first();

                    if (! $activeSession) {
                        Notification::make()
                            ->danger()
                            ->title(__('Active Cash Register Session Required'))
                            ->body(__('You must open a cash register session to clear cheques.'))
                            ->send();

                        return;
                    }

                    DB::transaction(function () use ($record, $data, $user, $activeSession) {
                        $record->update([
                            'status' => PdcStatus::Cleared,
                            'cleared_date' => $data['cleared_date'],
                        ]);

                        CashbookEntry::create([
                            'cash_register_session_id' => $activeSession->id,
                            'entry_type' => 'ChequeClearance',
                            'direction' => TransactionDirection::In,
                            'payment_method' => PaymentMethod::Bank,
                            'amount' => $record->amount,
                            'reference_type' => PostDatedCheque::class,
                            'reference_id' => $record->id,
                            'notes' => __('Cleared Cheque #').$record->cheque_number,
                            'created_by' => $user->id,
                        ]);
                    });

                    Notification::make()
                        ->title(__('Cheque Cleared'))
                        ->success()
                        ->send();
                }),

            Action::make('bounce')
                ->label(__('Bounce'))
                ->color('danger')
                ->visible(fn ($record) => $record && $record->status === PdcStatus::Deposited)
                ->form([
                    Textarea::make('bounce_reason')
                        ->label(__('Bounce Reason'))
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    $record->update([
                        'status' => PdcStatus::Bounced,
                        'bounce_reason' => $data['bounce_reason'],
                    ]);

                    // Send SMS notification
                    $smsService = app(SmsService::class);
                    $customerName = $record->customer->name;
                    $chequeNumber = $record->cheque_number;
                    $bankName = $record->bank_name;
                    $amount = number_format($record->amount, 2);
                    $reason = $data['bounce_reason'];

                    $message = "Dear {$customerName}, your cheque #{$chequeNumber} of BDT {$amount} from {$bankName} has bounced. Reason: {$reason}.";

                    $smsService->send($record->customer->mobile, $message);

                    Notification::make()
                        ->title(__('Cheque Bounced & SMS Logged'))
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        if ($this->record && in_array($this->record->status, [PdcStatus::Cleared, PdcStatus::Bounced])) {
            return [];
        }

        return parent::getFormActions();
    }
}
