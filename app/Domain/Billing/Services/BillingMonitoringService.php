<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\CreditTransaction;
use App\Domain\Billing\Models\ProcessedWebhookEvent;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BillingMonitoringService
{
    /**
     * Record a credit usage metric.
     * Useful for tracking credits burned per operation type.
     */
    public function recordCreditUsage(User $user, int $amount, string $operation, array $metadata = []): void
    {
        $key = "billing:credit_usage:{$operation}:" . now()->format('Y-m-d');

        // Increment daily counter
        Cache::increment($key, $amount);
        Cache::put($key . ':last_updated', now(), now()->addDays(7));

        // Log for analytics
        Log::channel('billing')->info('Credit usage recorded', [
            'user_id' => $user->id,
            'operation' => $operation,
            'amount' => $amount,
            'user_plan' => $user->current_plan_key,
            'remaining_balance' => $user->credits_balance,
            'metadata' => $metadata,
        ]);

        // Alert if user approaching zero balance
        if ($user->credits_balance <= 10 && $user->credits_balance > 0) {
            $this->alertLowBalance($user);
        }
    }

    /**
     * Record a payment failure.
     */
    public function recordPaymentFailure(User $user, array $context = []): void
    {
        Log::channel('billing')->error('Payment failure recorded', array_merge([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_plan' => $user->current_plan_key,
            'failure_count' => $user->payment_failure_count,
            'last_failure_at' => $user->last_payment_failure_at?->toIso8601String(),
        ], $context));

        // Alert if too many failures
        if ($user->payment_failure_count >= 3) {
            $this->alertHighFailureRate($user);
        }

        // Track daily failure rate
        $key = 'billing:payment_failures:' . now()->format('Y-m-d');
        Cache::increment($key);
    }

    /**
     * Record webhook processing latency.
     */
    public function recordWebhookLatency(string $eventType, int $delaySeconds, array $context = []): void
    {
        Log::channel('billing')->info('Webhook latency recorded', array_merge([
            'event_type' => $eventType,
            'delay_seconds' => $delaySeconds,
            'threshold_exceeded' => $delaySeconds > 3600, // 1 hour
        ], $context));

        // Alert if webhook delayed > 1 hour
        if ($delaySeconds > 3600) {
            $this->alertWebhookDelay($eventType, $delaySeconds, $context);
        }
    }

    /**
     * Check for missed credit allocations.
     * Should be run daily via scheduled command.
     */
    public function checkMissedAllocations(): array
    {
        $missedAllocations = [];

        // Find users with active subscriptions but no recent credit allocation
        $users = User::whereNotNull('stripe_id')
            ->where('current_plan_key', '!=', 'free')
            ->get();

        foreach ($users as $user) {
            // Check if user has received credits in the last 35 days
            // (30 days billing cycle + 5 day buffer)
            $recentAllocation = CreditTransaction::where('user_id', $user->id)
                ->where('type', 'subscription')
                ->where('created_at', '>=', now()->subDays(35))
                ->exists();

            if (!$recentAllocation && $user->subscribed('default')) {
                $missedAllocations[] = [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'plan' => $user->current_plan_key,
                    'credits_balance' => $user->credits_balance,
                ];

                Log::channel('billing')->error('Missed credit allocation detected', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'plan' => $user->current_plan_key,
                ]);
            }
        }

        if (count($missedAllocations) > 0) {
            $this->alertMissedAllocations($missedAllocations);
        }

        return $missedAllocations;
    }

    /**
     * Get daily billing metrics.
     */
    public function getDailyMetrics(?\Carbon\Carbon $date = null): array
    {
        $date = $date ?? now();
        $dateKey = $date->format('Y-m-d');

        return [
            'date' => $dateKey,
            'credit_usage' => [
                'total' => Cache::get("billing:credit_usage:total:{$dateKey}", 0),
                'by_operation' => $this->getCreditUsageByOperation($dateKey),
            ],
            'payment_failures' => Cache::get("billing:payment_failures:{$dateKey}", 0),
            'webhooks_processed' => ProcessedWebhookEvent::whereDate('created_at', $date)->count(),
            'refunds_processed' => ProcessedWebhookEvent::whereDate('created_at', $date)
                ->where('event_type', 'charge.refunded')
                ->count(),
            'disputes_created' => ProcessedWebhookEvent::whereDate('created_at', $date)
                ->where('event_type', 'charge.dispute.created')
                ->count(),
        ];
    }

    /**
     * Get credit usage breakdown by operation type.
     */
    protected function getCreditUsageByOperation(string $dateKey): array
    {
        $operations = ['api_call', 'generation', 'analysis', 'export'];
        $breakdown = [];

        foreach ($operations as $operation) {
            $breakdown[$operation] = Cache::get("billing:credit_usage:{$operation}:{$dateKey}", 0);
        }

        return $breakdown;
    }

    /**
     * Alert: Low credit balance.
     */
    protected function alertLowBalance(User $user): void
    {
        $cacheKey = "billing:alert:low_balance:{$user->id}";

        // Only alert once per day
        if (Cache::has($cacheKey)) {
            return;
        }

        Log::channel('billing')->warning('Low credit balance', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'balance' => $user->credits_balance,
        ]);

        // Send notification to user
        // Mail::to($user->email)->send(new LowCreditBalanceNotification($user));

        Cache::put($cacheKey, true, now()->addDay());
    }

    /**
     * Alert: High payment failure rate.
     */
    protected function alertHighFailureRate(User $user): void
    {
        $cacheKey = "billing:alert:high_failures:{$user->id}";

        // Only alert once per week
        if (Cache::has($cacheKey)) {
            return;
        }

        Log::channel('billing')->error('High payment failure rate', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'failure_count' => $user->payment_failure_count,
            'requires_investigation' => true,
        ]);

        // Send notification to admin
        // Mail::to(config('app.admin_email'))->send(new HighFailureRateNotification($user));

        Cache::put($cacheKey, true, now()->addWeek());
    }

    /**
     * Alert: Webhook processing delayed.
     */
    protected function alertWebhookDelay(string $eventType, int $delaySeconds, array $context): void
    {
        Log::channel('billing')->error('Webhook processing delayed', [
            'event_type' => $eventType,
            'delay_seconds' => $delaySeconds,
            'delay_minutes' => round($delaySeconds / 60, 2),
            'context' => $context,
            'requires_investigation' => true,
        ]);

        // Send notification to admin
        // Mail::to(config('app.admin_email'))->send(new WebhookDelayNotification($eventType, $delaySeconds));
    }

    /**
     * Alert: Missed credit allocations detected.
     */
    protected function alertMissedAllocations(array $missedAllocations): void
    {
        Log::channel('billing')->error('Missed credit allocations detected', [
            'count' => count($missedAllocations),
            'users' => $missedAllocations,
            'requires_immediate_action' => true,
        ]);

        // Send notification to admin
        // Mail::to(config('app.admin_email'))->send(new MissedAllocationsNotification($missedAllocations));
    }

    /**
     * Get system health metrics.
     */
    public function getHealthMetrics(): array
    {
        $last24Hours = now()->subDay();

        return [
            'status' => 'healthy', // or 'warning', 'critical'
            'checks' => [
                'webhooks' => [
                    'status' => $this->checkWebhookHealth($last24Hours),
                    'last_processed' => ProcessedWebhookEvent::latest()->first()?->created_at,
                    'processed_24h' => ProcessedWebhookEvent::where('created_at', '>=', $last24Hours)->count(),
                ],
                'payments' => [
                    'status' => $this->checkPaymentHealth($last24Hours),
                    'failures_24h' => Cache::get('billing:payment_failures:' . now()->format('Y-m-d'), 0),
                ],
                'refunds' => [
                    'status' => 'healthy',
                    'processed_24h' => ProcessedWebhookEvent::where('created_at', '>=', $last24Hours)
                        ->where('event_type', 'charge.refunded')
                        ->count(),
                ],
                'disputes' => [
                    'status' => $this->checkDisputeHealth($last24Hours),
                    'created_24h' => ProcessedWebhookEvent::where('created_at', '>=', $last24Hours)
                        ->where('event_type', 'charge.dispute.created')
                        ->count(),
                ],
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    protected function checkWebhookHealth(\Carbon\Carbon $since): string
    {
        $count = ProcessedWebhookEvent::where('created_at', '>=', $since)->count();

        if ($count === 0) {
            return 'warning'; // No webhooks in 24h might be unusual
        }

        return 'healthy';
    }

    protected function checkPaymentHealth(\Carbon\Carbon $since): string
    {
        $failures = Cache::get('billing:payment_failures:' . now()->format('Y-m-d'), 0);

        if ($failures > 10) {
            return 'critical';
        } elseif ($failures > 5) {
            return 'warning';
        }

        return 'healthy';
    }

    protected function checkDisputeHealth(\Carbon\Carbon $since): string
    {
        $disputes = ProcessedWebhookEvent::where('created_at', '>=', $since)
            ->where('event_type', 'charge.dispute.created')
            ->count();

        if ($disputes > 5) {
            return 'critical';
        } elseif ($disputes > 0) {
            return 'warning';
        }

        return 'healthy';
    }
}
