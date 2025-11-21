<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SECURITY CRITICAL: Adds CHECK constraint to prevent negative credit balances.
     * This is a database-level enforcement that prevents race conditions or bugs
     * from causing negative balances, which could allow unlimited free usage.
     */
    public function up(): void
    {
        // PostgreSQL syntax for CHECK constraint
        DB::statement('ALTER TABLE users ADD CONSTRAINT credits_balance_non_negative CHECK (credits_balance >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS credits_balance_non_negative');
    }
};
