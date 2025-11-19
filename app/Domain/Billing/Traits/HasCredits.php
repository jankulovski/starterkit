<?php

namespace App\Domain\Billing\Traits;

use App\Domain\Billing\Models\CreditTransaction;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCredits
{
    /**
     * Get the user's credit balance.
     */
    public function creditsBalance(): int
    {
        return $this->credits_balance ?? 0;
    }

    /**
     * Check if the user has enough credits.
     */
    public function hasCredits(int $amount): bool
    {
        return $this->creditsBalance() >= $amount;
    }

    /**
     * Add credits to the user's account.
     */
    public function addCredits(int $amount, string $type, ?string $description = null, ?array $metadata = null): self
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        $this->increment('credits_balance', $amount);

        $this->creditTransactions()->create([
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
        ]);

        return $this;
    }

    /**
     * Charge (deduct) credits from the user's account.
     */
    public function chargeCredits(int $amount, string $context, ?array $metadata = null): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        if (! $this->hasCredits($amount)) {
            return false;
        }

        $this->decrement('credits_balance', $amount);

        $this->creditTransactions()->create([
            'amount' => -$amount,
            'type' => 'usage',
            'description' => "Used {$amount} credits for: {$context}",
            'metadata' => array_merge($metadata ?? [], ['context' => $context]),
        ]);

        return true;
    }

    /**
     * Get all credit transactions for this user.
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }
}

