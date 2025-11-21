<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SECURITY: Adding balance_after column for audit trail.
     * This allows reconstruction of balance history and detection of
     * race conditions or corruption in credit transactions.
     */
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            // Records the user's credit balance AFTER this transaction was applied
            // This creates an audit trail to verify transaction integrity
            $table->integer('balance_after')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropColumn('balance_after');
        });
    }
};
