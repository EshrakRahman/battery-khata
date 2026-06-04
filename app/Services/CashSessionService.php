<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\ActiveCashSessionExistsException;
use App\Exceptions\InvalidDenominationsTotalException;
use App\Models\CashbookEntry;
use App\Models\CashRegisterSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CashSessionService
{
    /**
     * Open a new cash session for a user.
     */
    public function openSession(User $user, float|string $openingCash, ?string $notes = null): CashRegisterSession
    {
        return DB::transaction(function () use ($user, $openingCash, $notes) {
            // Check for existing active session
            $activeSession = CashRegisterSession::where('opened_by', $user->id)
                ->whereNull('closed_at')
                ->first();

            if ($activeSession) {
                throw new ActiveCashSessionExistsException("User {$user->name} already has an active cash register session.");
            }

            return CashRegisterSession::create([
                'opened_by' => $user->id,
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
     */
    public function closeSession(
        CashRegisterSession $session,
        float|string $closingCash,
        array $denominations,
        ?string $notes = null
    ): CashRegisterSession {
        return DB::transaction(function () use ($session, $closingCash, $denominations, $notes) {
            // 1. Audit denominations count
            $denomSum = 0.00;
            foreach ($denominations as $note => $qty) {
                $denomSum += ((float) $note) * ((int) $qty);
            }

            if (abs($denomSum - (float) $closingCash) > 0.001) {
                $denomSumStr = number_format($denomSum, 2, '.', '');
                $closingCashStr = number_format((float) $closingCash, 2, '.', '');
                throw new InvalidDenominationsTotalException(
                    "The sum of note denominations ({$denomSumStr}) does not match the declared closing cash ({$closingCashStr})."
                );
            }

            // 2. Fetch all cash-only cashbook entries linked to this session
            $cashEntries = CashbookEntry::where('cash_register_session_id', $session->id)
                ->where('payment_method', PaymentMethod::Cash)
                ->get();

            $inflow = 0.00;
            $outflow = 0.00;

            foreach ($cashEntries as $entry) {
                $amount = (float) $entry->amount;
                if (strcasecmp($entry->direction, 'In') === 0) {
                    $inflow += $amount;
                } elseif (strcasecmp($entry->direction, 'Out') === 0) {
                    $outflow += $amount;
                }
            }

            // Expected cash = Opening Cash + Cash In - Cash Out
            $expectedCash = (float) $session->opening_cash + $inflow - $outflow;
            $shortageExcess = (float) $closingCash - $expectedCash;

            // 3. Update session
            $session->update([
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'closing_cash' => $closingCash,
                'shortage_excess' => $shortageExcess,
                'denominations' => $denominations,
                'notes' => $notes ?? $session->notes,
            ]);

            return $session->refresh();
        });
    }
}
