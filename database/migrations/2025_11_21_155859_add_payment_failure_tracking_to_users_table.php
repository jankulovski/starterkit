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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('payment_failure_count')->default(0)->after('stripe_id');
            $table->timestamp('last_payment_failure_at')->nullable()->after('payment_failure_count');
            $table->timestamp('payment_failure_notified_at')->nullable()->after('last_payment_failure_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['payment_failure_count', 'last_payment_failure_at', 'payment_failure_notified_at']);
        });
    }
};
