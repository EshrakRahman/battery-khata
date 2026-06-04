<?php

namespace App\Services;

use App\Models\Broker;
use App\Models\BrokerLedger;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class LedgerService
{
    /**
     * Record a customer ledger entry with transactional locking.
     *
     * @throws Throwable
     */
    public function recordCustomerTransaction(
        Customer $customer,
        float|string $debit,
        float|string $credit,
        string $transactionType,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $transactionDate = null
    ): CustomerLedger {
        /** @var CustomerLedger */
        return $this->recordTransaction(
            parent: $customer,
            ledgerClass: CustomerLedger::class,
            foreignKeyName: 'customer_id',
            debit: $debit,
            credit: $credit,
            transactionType: $transactionType,
            reference: $reference,
            notes: $notes,
            transactionDate: $transactionDate,
            isReceivable: true
        );
    }

    /**
     * Record a supplier ledger entry with transactional locking.
     *
     * @throws Throwable
     */
    public function recordSupplierTransaction(
        Supplier $supplier,
        float|string $debit,
        float|string $credit,
        string $transactionType,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $transactionDate = null
    ): SupplierLedger {
        /** @var SupplierLedger */
        return $this->recordTransaction(
            parent: $supplier,
            ledgerClass: SupplierLedger::class,
            foreignKeyName: 'supplier_id',
            debit: $debit,
            credit: $credit,
            transactionType: $transactionType,
            reference: $reference,
            notes: $notes,
            transactionDate: $transactionDate,
            isReceivable: false
        );
    }

    /**
     * Record a broker ledger entry with transactional locking.
     *
     * @throws Throwable
     */
    public function recordBrokerTransaction(
        Broker $broker,
        float|string $debit,
        float|string $credit,
        string $transactionType,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $transactionDate = null
    ): BrokerLedger {
        /** @var BrokerLedger */
        return $this->recordTransaction(
            parent: $broker,
            ledgerClass: BrokerLedger::class,
            foreignKeyName: 'broker_id',
            debit: $debit,
            credit: $credit,
            transactionType: $transactionType,
            reference: $reference,
            notes: $notes,
            transactionDate: $transactionDate,
            isReceivable: false
        );
    }

    /**
     * Generic helper to record a ledger transaction with row locking and running balance calculations.
     *
     * @param  class-string<Model>  $ledgerClass  The ledger model class (CustomerLedger, SupplierLedger, or BrokerLedger)
     *
     * @throws Throwable
     */
    private function recordTransaction(
        Model $parent,
        string $ledgerClass,
        string $foreignKeyName,
        float|string $debit,
        float|string $credit,
        string $transactionType,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $transactionDate = null,
        bool $isReceivable = true
    ): Model {
        return DB::transaction(function () use (
            $parent,
            $ledgerClass,
            $foreignKeyName,
            $debit,
            $credit,
            $transactionType,
            $reference,
            $notes,
            $transactionDate,
            $isReceivable
        ) {
            // Lock the parent model record to serialize running balance calculations
            $lockedParent = $parent::query()->where('id', $parent->getKey())->lockForUpdate()->firstOrFail();

            // Get the latest ledger entry
            $latest = $ledgerClass::query()->where($foreignKeyName, $lockedParent->getKey())
                ->latest('id')
                ->first();

            $prevBalance = $latest?->getAttribute('running_balance') ?? '0.00';

            // Running Balance:
            // - For Receivables: prevBalance + debit - credit
            // - For Payables: prevBalance + credit - debit
            $runningBalance = $isReceivable
                ? bcsub(bcadd((string) $prevBalance, (string) $debit, 2), (string) $credit, 2)
                : bcsub(bcadd((string) $prevBalance, (string) $credit, 2), (string) $debit, 2);

            return $ledgerClass::query()->create([
                $foreignKeyName => $lockedParent->getKey(),
                'transaction_date' => $transactionDate ?? now(),
                'transaction_type' => $transactionType,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'notes' => $notes,
            ]);
        });
    }
}
