<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('national_id')->unique()->nullable();
            $table->string('image_path')->nullable();
            $table->string('division')->nullable();
            $table->string('district')->nullable();
            $table->string('upazila')->nullable();
            $table->text('address')->nullable();
            $table->string('customer_type')->default('Retail'); // Retail, Dealer, Garage
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Enable PostgreSQL fuzzy trigram matching and compile GIN indexes on lookup fields if pgsql
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX customers_name_trgm_idx ON customers USING gin (name gin_trgm_ops)');
            DB::statement('CREATE INDEX customers_mobile_trgm_idx ON customers USING gin (mobile gin_trgm_ops)');
            DB::statement('CREATE INDEX customers_national_id_trgm_idx ON customers USING gin (national_id gin_trgm_ops)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS customers_name_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS customers_mobile_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS customers_national_id_trgm_idx');
        }
        Schema::dropIfExists('customers');
    }
};
