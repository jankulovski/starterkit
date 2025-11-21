<?php

namespace App\Console\Commands;

use App\Domain\Billing\Models\ProcessedWebhookEvent;
use App\Domain\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckWebhookHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:check-webhook-health {--alert : Send alert if issues detected}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor webhook delivery and processing health';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking webhook health...');
        $this->newLine();

        $hasIssues = false;

        // Check 1: Recent webhook activity (last 24 hours)
        $recentWebhooks = ProcessedWebhookEvent::where('created_at', '>=', now()->subDay())->count();
        $this->line("📊 Webhooks processed (last 24h): {$recentWebhooks}");

        // Check 2: Webhook activity by type (last 7 days)
        $this->newLine();
        $this->line('📈 Webhook activity (last 7 days):');

        $eventTypes = ProcessedWebhookEvent::where('created_at', '>=', now()->subWeek())
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->get();

        foreach ($eventTypes as $eventType) {
            $this->line("  - {$eventType->event_type}: {$eventType->count}");
        }

        if ($eventTypes->isEmpty()) {
            $this->warn('  ⚠️  No webhooks processed in the last 7 days!');
            $hasIssues = true;
        }

        // Check 3: Active subscriptions vs recent subscription webhooks
        $this->newLine();
        $activeSubscribers = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active');
        })->count();

        $recentSubscriptionEvents = ProcessedWebhookEvent::where('created_at', '>=', now()->subDays(30))
            ->whereIn('event_type', [
                'customer.subscription.created',
                'invoice.payment_succeeded',
            ])
            ->count();

        $this->line("👥 Active subscribers: {$activeSubscribers}");
        $this->line("📨 Subscription webhooks (last 30 days): {$recentSubscriptionEvents}");

        // Expected: At least 1 webhook per subscriber per month (renewals)
        if ($activeSubscribers > 0 && $recentSubscriptionEvents === 0) {
            $this->warn('⚠️  WARNING: Active subscribers but no recent subscription webhooks!');
            $this->warn('   This may indicate webhook delivery issues.');
            $hasIssues = true;
        }

        // Check 4: Payment failures vs active subscriptions
        $this->newLine();
        $usersWithFailures = User::where('payment_failure_count', '>', 0)->count();
        $this->line("💳 Users with payment failures: {$usersWithFailures}");

        if ($usersWithFailures > ($activeSubscribers * 0.1)) { // More than 10% failure rate
            $this->warn('⚠️  WARNING: High payment failure rate (>10% of active subscribers)');
            $hasIssues = true;
        }

        // Check 5: Users with past_due subscriptions
        $pastDueCount = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'past_due');
        })->count();

        $this->line("⏰ Subscriptions past due: {$pastDueCount}");

        if ($pastDueCount > 0) {
            $this->warn("⚠️  {$pastDueCount} subscription(s) are past due and need attention");
            $hasIssues = true;
        }

        // Check 6: Oldest unprocessed webhook (if any stuck in queue)
        $oldestPending = ProcessedWebhookEvent::where('created_at', '<', now()->subHours(2))
            ->orderBy('created_at', 'asc')
            ->first();

        if ($oldestPending) {
            $hoursOld = now()->diffInHours($oldestPending->created_at);
            $this->newLine();
            $this->warn("⚠️  Oldest processed webhook is {$hoursOld} hours old");
            $this->warn("   This might indicate delayed processing");
        }

        // Summary
        $this->newLine();
        if ($hasIssues) {
            $this->error('❌ Webhook health check FAILED - Issues detected');

            if ($this->option('alert')) {
                Log::error('Webhook health check failed', [
                    'active_subscribers' => $activeSubscribers,
                    'recent_webhooks' => $recentWebhooks,
                    'payment_failures' => $usersWithFailures,
                    'past_due' => $pastDueCount,
                ]);
                $this->warn('   Alert logged for monitoring system');
            }

            return Command::FAILURE;
        } else {
            $this->info('✅ Webhook health check PASSED - All systems normal');
            return Command::SUCCESS;
        }
    }
}
