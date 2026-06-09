<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\InvoiceStatus;
use App\Enums\TransactionDirection;
use App\Filament\Resources\CashRegisterSessions\CashRegisterSessionResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\LedgerService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

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
                ->body(__('An active cash register session is required to record customer payments.'))
                ->send();

            $this->redirect(CashRegisterSessionResource::getUrl('index'));
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $activeSession = CashRegisterSession::where('opened_by', $user->id)
                ->whereNull('closed_at')
                ->firstOrFail();

            // 1. Create the Payment record
            $payment = Payment::create([
                'customer_id' => $data['customer_id'],
                'cash_register_session_id' => $activeSession->id,
                'payment_date' => $data['payment_date'] ?? now(),
                'payment_method' => $data['payment_method'],
                'total_amount' => $data['total_amount'],
                'service_charge' => $data['service_charge'] ?? 0.00,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $user->id,
            ]);

            // 2. Record CustomerLedger Credit Transaction (reducing receivables)
            $ledgerService = app(LedgerService::class);
            $customer = Customer::findOrFail($payment->customer_id);
            $ledgerService->recordCustomerTransaction(
                $customer,
                0.00,
                $payment->total_amount,
                'Payment',
                $payment,
                __('Customer credit payment logged #').$payment->id
            );

            // 3. Record CashbookEntry inflow transaction
            CashbookEntry::create([
                'cash_register_session_id' => $activeSession->id,
                'entry_type' => 'Payment',
                'direction' => TransactionDirection::In,
                'payment_method' => $payment->payment_method,
                'amount' => $payment->total_amount,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'notes' => $payment->notes,
                'created_by' => $user->id,
            ]);

            // 4. FIFO Invoice Payment Allocation
            $unallocatedAmount = (float) $payment->total_amount;

            if ($unallocatedAmount > 0) {
                $invoices = Invoice::where('customer_id', $payment->customer_id)
                    ->where('invoice_status', InvoiceStatus::Completed)
                    ->get()
                    ->map(function ($invoice) {
                        $allocated = PaymentAllocation::where('invoice_id', $invoice->id)->sum('allocated_amount');
                        $invoice->outstanding_balance = max(0, (float) $invoice->grand_total - (float) $allocated);

                        return $invoice;
                    })
                    ->filter(fn ($invoice) => $invoice->outstanding_balance > 0)
                    ->sortBy('invoice_date'); // FIFO

                foreach ($invoices as $invoice) {
                    if ($unallocatedAmount <= 0) {
                        break;
                    }

                    $allocation = min($unallocatedAmount, $invoice->outstanding_balance);

                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'allocated_amount' => $allocation,
                    ]);

                    $unallocatedAmount -= $allocation;
                }
            }

            return $payment;
        });
    }
}
