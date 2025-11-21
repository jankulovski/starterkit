<?php

namespace App\Domain\Billing\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks processed Stripe webhook events for idempotency.
 *
 * SECURITY: Prevents duplicate processing of webhook events, which could
 * lead to double credit allocation or duplicate charges.
 */
class ProcessedWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event_type',
        'user_id',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Check if an event has already been processed.
     */
    public static function isProcessed(string $eventId): bool
    {
        return static::where('event_id', $eventId)->exists();
    }

    /**
     * Mark an event as processed.
     */
    public static function markAsProcessed(
        string $eventId,
        string $eventType,
        ?int $userId = null,
        ?array $payload = null
    ): self {
        return static::create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'user_id' => $userId,
            'payload' => $payload,
            'processed_at' => now(),
        ]);
    }

    /**
     * Get the user associated with this webhook event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
