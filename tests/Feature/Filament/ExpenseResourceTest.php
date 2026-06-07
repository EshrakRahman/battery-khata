<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionDirection;
use App\Enums\UserRole;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Models\CashRegisterSession;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $this->counterBoy = User::create([
        'name' => 'Counter Boy',
        'email' => 'counter@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::CounterBoy,
        'is_active' => true,
    ]);

    $this->category = ExpenseCategory::create([
        'name' => 'Utilities',
    ]);

    $this->session = CashRegisterSession::create([
        'opened_by' => $this->admin->id,
        'opened_at' => now(),
        'opening_cash' => 1000.00,
        'expected_cash' => 1000.00,
    ]);
});

test('admin can list expenses', function () {
    $this->actingAs($this->admin);

    $expense = Expense::create([
        'expense_category_id' => $this->category->id,
        'cash_register_session_id' => $this->session->id,
        'amount' => 500.00,
        'payment_method' => PaymentMethod::Cash,
        'expense_date' => now()->toDateString(),
        'notes' => 'Electric bill',
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(ListExpenses::class)
        ->assertCanSeeTableRecords([$expense])
        ->assertCanRenderTableColumn('id')
        ->assertCanRenderTableColumn('category.name')
        ->assertCanRenderTableColumn('amount')
        ->assertCanRenderTableColumn('payment_method')
        ->assertCanRenderTableColumn('expense_date');
});

test('admin can create expense with active register session which creates cashbook entry', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateExpense::class)
        ->fillForm([
            'expense_category_id' => $this->category->id,
            'amount' => 350.00,
            'payment_method' => PaymentMethod::Cash->value,
            'expense_date' => now()->toDateString(),
            'notes' => 'Tea and snacks',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('expenses', [
        'expense_category_id' => $this->category->id,
        'amount' => 350.00,
        'created_by' => $this->admin->id,
    ]);

    $expense = Expense::where('amount', 350.00)->first();

    $this->assertDatabaseHas('cashbook_entries', [
        'cash_register_session_id' => $this->session->id,
        'entry_type' => 'Expense',
        'direction' => TransactionDirection::Out->value,
        'payment_method' => PaymentMethod::Cash->value,
        'amount' => 350.00,
        'reference_type' => Expense::class,
        'reference_id' => $expense->id,
    ]);
});

test('cannot load create expense page without active register session', function () {
    // Log in as counter boy who has NO active session
    $this->actingAs($this->counterBoy);

    Livewire::test(CreateExpense::class)
        ->assertRedirect();
});

test('updating an expense updates the corresponding cashbook entry', function () {
    $this->actingAs($this->admin);

    $expense = Expense::create([
        'expense_category_id' => $this->category->id,
        'cash_register_session_id' => $this->session->id,
        'amount' => 500.00,
        'payment_method' => PaymentMethod::Cash,
        'expense_date' => now()->toDateString(),
        'notes' => 'Electric bill',
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(EditExpense::class, ['record' => $expense->getKey()])
        ->fillForm([
            'amount' => 600.00,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'amount' => 600.00,
    ]);

    $this->assertDatabaseHas('cashbook_entries', [
        'reference_type' => Expense::class,
        'reference_id' => $expense->id,
        'amount' => 600.00,
    ]);
});

test('deleting an expense deletes the corresponding cashbook entry', function () {
    $this->actingAs($this->admin);

    $expense = Expense::create([
        'expense_category_id' => $this->category->id,
        'cash_register_session_id' => $this->session->id,
        'amount' => 500.00,
        'payment_method' => PaymentMethod::Cash,
        'expense_date' => now()->toDateString(),
        'notes' => 'Electric bill',
        'created_by' => $this->admin->id,
    ]);

    $this->assertDatabaseHas('cashbook_entries', [
        'reference_type' => Expense::class,
        'reference_id' => $expense->id,
    ]);

    $expense->delete();

    $this->assertDatabaseMissing('cashbook_entries', [
        'reference_type' => Expense::class,
        'reference_id' => $expense->id,
    ]);
});
