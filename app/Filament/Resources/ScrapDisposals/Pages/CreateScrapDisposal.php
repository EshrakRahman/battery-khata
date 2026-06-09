<?php

namespace App\Filament\Resources\ScrapDisposals\Pages;

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Filament\Resources\ScrapDisposals\ScrapDisposalResource;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\ScrapDisposal;
use App\Models\Supplier;
use App\Services\LedgerService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateScrapDisposal extends CreateRecord
{
    protected static string $resource = ScrapDisposalResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $paymentMethod = PaymentMethod::from($data['payment_method']);

            $activeSession = null;
            if ($paymentMethod !== PaymentMethod::ScrapAdjustment) {
                $activeSession = CashRegisterSession::where('opened_by', $user->id)
                    ->whereNull('closed_at')
                    ->first();

                if (! $activeSession) {
                    throw ValidationException::withMessages([
                        'payment_method' => __('An active cash register session is required for cash/bank scrap disposals.'),
                    ]);
                }
            }

            // 1. Create ScrapDisposal
            $disposal = ScrapDisposal::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'disposal_date' => $data['disposal_date'] ?? now(),
                'payment_method' => $paymentMethod,
                'total_received' => $data['total_received'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
            // 3. Process Accounting
            if ($paymentMethod === PaymentMethod::ScrapAdjustment) {
                $supplier = Supplier::findOrFail($disposal->supplier_id);
                app(LedgerService::class)->recordSupplierTransaction(
                    $supplier,
                    0.00, // debit
                    $disposal->total_received, // credit
                    'ScrapAdjustment',
                    $disposal,
                    __('Scrap disposal ledger offset #').$disposal->id
                );
            } else {
                CashbookEntry::create([
                    'cash_register_session_id' => $activeSession->id,
                    'entry_type' => 'ScrapDisposal',
                    'direction' => TransactionDirection::In,
                    'payment_method' => $paymentMethod,
                    'amount' => $disposal->total_received,
                    'reference_type' => ScrapDisposal::class,
                    'reference_id' => $disposal->id,
                    'notes' => $disposal->notes,
                    'created_by' => $user->id,
                ]);
            }

            return $disposal;
        });
    }

    protected function afterCreate(): void
    {
        $this->record->collections()->update([
            'status' => 'Disposed',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
