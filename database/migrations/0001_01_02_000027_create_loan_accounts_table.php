<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('lender_type'); // LenderType Enum (Supplier, Customer, Staff, External)
            $table->unsignedBigInteger('lender_reference_id');
            $table->string('loan_name');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('outstanding_balance', 12, 2);
            $table->date('start_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_accounts');
    }
};
