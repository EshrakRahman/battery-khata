<?php

use App\Enums\LenderType;
use App\Enums\PaymentMethod;
use App\Enums\PdcStatus;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\LoanAccount;
use App\Models\LoanTransaction;
use App\Models\Payment;
use App\Models\PostDatedCheque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('micro loans and staff advance Hawlat records', function () {
    $staff = User::create([
        'name' => 'Counter Boy Mamun',
        'email' => 'mamun@counter.com',
        'password' => bcrypt('password'),
        'role' => 'counter_boy',
    ]);

    $loan = LoanAccount::create([
        'lender_type' => LenderType::Staff,
        'lender_reference_id' => $staff->id,
        'loan_name' => 'Mamun Hawlat Advance',
        'principal_amount' => 5000.00,
        'outstanding_balance' => 5000.00,
        'start_date' => now()->toDateString(),
    ]);

    $repayment = LoanTransaction::create([
        'loan_account_id' => $loan->id,
        'transaction_date' => now()->toDateString(),
        'transaction_type' => 'Repayment',
        'amount' => 2000.00,
        'notes' => 'Mamun returned 2000 Tk',
    ]);

    expect($loan->lender_type)->toBe(LenderType::Staff)
        ->and($loan->principal_amount)->toEqual(5000.00)
        ->and($repayment->amount)->toEqual(2000.00)
        ->and($repayment->transaction_type)->toBe('Repayment');
});

test('daily overhead expenses', function () {
    $category = ExpenseCategory::create(['name' => 'Utility Bills']);
    $user = User::create([
        'name' => 'Counter Boy Mamun',
        'email' => 'mamun@counter.com',
        'password' => bcrypt('password'),
        'role' => 'counter_boy',
    ]);
    $session = CashRegisterSession::create(['opened_by' => $user->id, 'opened_at' => now()]);

    $expense = Expense::create([
        'expense_category_id' => $category->id,
        'cash_register_session_id' => $session->id,
        'amount' => 1200.00,
        'payment_method' => PaymentMethod::Cash,
        'expense_date' => now()->toDateString(),
        'notes' => 'Electric bill payout',
        'created_by' => $user->id,
    ]);

    expect($expense->category->name)->toBe('Utility Bills')
        ->and($expense->payment_method)->toBe(PaymentMethod::Cash)
        ->and($expense->amount)->toEqual(1200.00);
});

test('post dated cheques registry states', function () {
    $customer = Customer::create(['name' => 'Elite Wholesaler', 'mobile' => '01700998877']);
    $user = User::create([
        'name' => 'Counter Boy Mamun',
        'email' => 'mamun@counter.com',
        'password' => bcrypt('password'),
        'role' => 'counter_boy',
    ]);
    $session = CashRegisterSession::create(['opened_by' => $user->id, 'opened_at' => now()]);

    $payment = Payment::create([
        'customer_id' => $customer->id,
        'cash_register_session_id' => $session->id,
        'payment_date' => now(),
        'payment_method' => PaymentMethod::Cheque,
        'total_amount' => 35000.00,
        'received_by' => $user->id,
    ]);

    $pdc = PostDatedCheque::create([
        'customer_id' => $customer->id,
        'payment_id' => $payment->id,
        'cheque_number' => 'PDC-1029384',
        'bank_name' => 'Sonali Bank Ltd',
        'amount' => 35000.00,
        'maturity_date' => now()->addDays(30)->toDateString(),
        'status' => PdcStatus::Pending,
    ]);

    expect($pdc->status)->toBe(PdcStatus::Pending)
        ->and($pdc->amount)->toEqual(35000.00)
        ->and($pdc->payment->total_amount)->toEqual(35000.00);
});
