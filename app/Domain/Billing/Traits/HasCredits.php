<?php

namespace App\Domain\Billing\Traits;

use App\Domain\Billing\Models\CreditTransaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *
     * SECURITY: Uses pessimistic locking to prevent race conditions where
     * multiple concurrent requests could corrupt the credits balance.
     */
    public function addCredits(int $amount, string $type, ?string $description = null, ?array $metadata = null): self
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        DB::transaction(function () use ($amount, $type, $description, $metadata) {
            // Pessimistic lock: Locks the user row until transaction commits
            // This prevents concurrent credit operations from causing race conditions
            $user = static::lockForUpdate()->find($this->id);

            if (!$user) {
                throw new \RuntimeException('User not found during credit addition.');
            }

            // Increment within the locked transaction
            $user->credits_balance += $amount;
            $user->save();

            // Record transaction with balance_after for audit trail
            $user->creditTransactions()->create([
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'metadata' => $metadata,
                'balance_after' => $user->credits_balance,
            ]);

            // Log for audit purposes
            Log::info('Credits added', [
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'balance_after' => $user->credits_balance,
            ]);

            // Update current instance to reflect changes
            $this->credits_balance = $user->credits_balance;
        });

        return $this;
    }

    /**
     * Charge (deduct) credits from the user's account.
     *
     * SECURITY: Uses pessimistic locking to prevent race conditions.
     * Without locking, two concurrent requests could both pass the hasCredits()
     * check and deduct credits, resulting in negative balance or double charging.
     *
     * @return bool True if credits were successfully charged, false if insufficient credits
     */
    public function chargeCredits(int $amount, string $context, ?array $metadata = null): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($amount, $context, $metadata) {
            // Pessimistic lock: Locks the user row until transaction commits
            // This ensures the balance check and deduction are atomic
            $user = static::lockForUpdate()->find($this->id);

            if (!$user) {
                throw new \RuntimeException('User not found during credit charge.');
            }

            // Check balance within the locked transaction
            // This prevents race condition where multiple requests pass the check simultaneously
            if ($user->credits_balance < $amount) {
                Log::warning('Insufficient credits', [
                    'user_id' => $user->id,
                    'required' => $amount,
                    'available' => $user->credits_balance,
                    'context' => $context,
                ]);
                return false;
            }

            // Deduct credits within the locked transaction
            $user->credits_balance -= $amount;
            $user->save();

            // Record transaction with balance_after for audit trail
            $user->creditTransactions()->create([
                'amount' => -$amount,
                'type' => 'usage',
                'description' => "Used {$amount} credits for: {$context}",
                'metadata' => array_merge($metadata ?? [], ['context' => $context]),
                'balance_after' => $user->credits_balance,
            ]);

            // Log successful charge
            Log::info('Credits charged', [
                'user_id' => $user->id,
                'amount' => $amount,
                'context' => $context,
                'balance_after' => $user->credits_balance,
            ]);

            // Update current instance to reflect changes
            $this->credits_balance = $user->credits_balance;

            return true;
        });
    }

    /**
     * Get all credit transactions for this user.
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }
}

