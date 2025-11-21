<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SECURITY: This table tracks processed Stripe webhook events to prevent
     * duplicate processing (idempotency). Without this, network retries or
     * Stripe replays could cause double credit allocation or duplicate charges.
     */
    public function up(): void
    {
        Schema::create('processed_webhook_events', function (Blueprint $table) {
            $table->id();
            // Stripe event ID (evt_xxx or cs_xxx for sessions)
            $table->string('event_id', 255)->unique();
            // Event type (e.g., 'checkout.session.completed', 'invoice.payment_succeeded')
            $table->string('event_type', 100);
            // User affected by this webhook (nullable for non-user events)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            // Full event payload for debugging (JSON)
            $table->json('payload')->nullable();
            // When the event was processed
            $table->timestamp('processed_at');
            $table->timestamps();

            // Index for quick lookups
            $table->index(['event_id', 'event_type']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_webhook_events');
    }
};
