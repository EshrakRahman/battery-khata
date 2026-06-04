<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\ActiveCashSessionExistsException;
use App\Exceptions\InvalidDenominationsTotalException;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CashSessionService
{
    /**
     * Open a new cash session for a user.
     *
     * @throws Throwable
     * @throws ActiveCashSessionExistsException
     */
    public function openSession(User $user, float|string $openingCash, ?string $notes = null): CashRegisterSession
    {
        return DB::transaction(function () use ($user, $openingCash, $notes) {
            // Lock the parent User record to serialize session opening requests per user
            User::query()->where('id', $user->getKey())->lockForUpdate()->firstOrFail();

            // Check for existing active session with a row-level lock
            $activeSession = CashRegisterSession::query()->where('opened_by', $user->getKey())
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->first();

            if ($activeSession) {
                throw new ActiveCashSessionExistsException("User {$user->getAttribute('name')} already has an active cash register session.");
            }

            return CashRegisterSession::query()->create([
                'opened_by' => $user->getKey(),
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash, // initially expected cash matches opening cash
                'closing_cash' => 0.00,
                'shortage_excess' => 0.00,
                'denominations' => [],
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Close an active cash session with denomination reconciliation.
     *
     * @throws Throwable
     * @throws InvalidDenominationsTotalException
     */
    public function closeSession(
        CashRegisterSession $session,
        float|string $closingCash,
        array $denominations,
        ?string $notes = null
    ): CashRegisterSession {
        return DB::transaction(function () use ($session, $closingCash, $denominations, $notes) {
            // 1. Audit denominations count using BCMath
            $denomSum = '0.00';
            foreach ($denominations as $note => $qty) {
                $lineTotal = bcmul((string) $note, (string) $qty, 2);
                $denomSum = bcadd($denomSum, $lineTotal, 2);
            }

            if (bccomp($denomSum, (string) $closingCash, 2) !== 0) {
                $closingCashStr = number_format((float) $closingCash, 2, '.', '');
                throw new InvalidDenominationsTotalException(
                    "The sum of note denominations ({$denomSum}) does not match the declared closing cash ({$closingCashStr})."
                );
            }

            // 2. Aggregate cashbook entries directly in the database
            $aggregates = CashbookEntry::query()->where('cash_register_session_id', $session->getKey())
                ->where('payment_method', PaymentMethod::Cash)
                ->selectRaw("
                    SUM(CASE WHEN LOWER(direction) = 'in' THEN amount ELSE 0 END) as inflow,
                    SUM(CASE WHEN LOWER(direction) = 'out' THEN amount ELSE 0 END) as outflow
                ")
                ->first();

            $inflow = $aggregates ? $aggregates->getAttribute('inflow') : '0.00';
            $outflow = $aggregates ? $aggregates->getAttribute('outflow') : '0.00';

            $inflow = $inflow ?? '0.00';
            $outflow = $outflow ?? '0.00';

            // Expected cash = Opening Cash + Cash In - Cash Out (using BCMath)
            $expectedCash = bcsub(bcadd((string) $session->getAttribute('opening_cash'), (string) $inflow, 2), (string) $outflow, 2);
            $shortageExcess = bcsub((string) $closingCash, $expectedCash, 2);

            // 3. Update session
            $session->update([
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'closing_cash' => $closingCash,
                'shortage_excess' => $shortageExcess,
                'denominations' => $denominations,
                'notes' => $notes ?? $session->getAttribute('notes'),
            ]);

            return $session->refresh();
        });
    }
}
