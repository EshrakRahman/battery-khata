<?php

namespace App\Filament\Resources\SupplierPayments\Pages;

use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Filament\Resources\SupplierPayments\SupplierPaymentResource;
use App\Models\CashRegisterSession;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierPayment extends CreateRecord
{
    protected static string $resource = SupplierPaymentResource::class;

    public function mount(): void
    {
        parent::mount();

        $activeSession = CashRegisterSession::where('opened_by', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if (! $activeSession) {
            Notification::make()
                ->warning()
                ->title(__('Active Cash Register Session Required'))
                ->body(__('An active cash register session is required to record supplier payments.'))
                ->send();

            $this->redirect(CashRegisterSessionResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $activeSession = CashRegisterSession::where('opened_by', $user->id)
            ->whereNull('closed_at')
            ->first();

        if (! $activeSession) {
            throw new \Exception(__('An active cash register session is required to perform this action.'));
        }

        $data['created_by'] = $user->id;
        $data['cash_register_session_id'] = $activeSession->id;

        return $data;
    }
}
