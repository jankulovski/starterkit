<?php

namespace App\Console\Commands;

use App\Domain\Users\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:reset-monthly-credits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconciliation job to detect and fix missed monthly credit allocations';

    /**
     * Execute the console command.
     *
     * This command acts as a backup reconciliation mechanism to catch missed webhook allocations.
     * It checks each active subscriber to see if they've received their monthly credits recently.
     * If a user hasn't received credits in the last 25 days, it allocates them (likely a missed webhook).
     */
    public function handle(): int
    {
        $this->info('Starting credit reconciliation...');

        $users = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active')
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                });
        })->get();

        $allocatedCount = 0;
        $skippedCount = 0;
        $missedAllocations = [];

        foreach ($users as $user) {
            $plan = $user->currentPlan();
            if (! $plan) {
                continue;
            }

            $monthlyCredits = $plan['monthly_credits'] ?? 0;

            if ($monthlyCredits <= 0) {
                continue;
            }

            // Check if user has received subscription credits in the last 25 days
            // (normal billing cycle is 30 days, so 25 gives 5-day buffer)
            $lastAllocation = \App\Domain\Billing\Models\CreditTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'subscription')
                ->where('amount', '>', 0)
                ->where('created_at', '>=', now()->subDays(25))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastAllocation) {
                // User already received credits recently via webhook - skip
                $skippedCount++;
                continue;
            }

            // Likely a missed allocation - add credits now
            $user->addCredits(
                $monthlyCredits,
                'subscription',
                "Monthly credits for {$plan['name']} plan (reconciliation)",
                ['reconciliation' => true]
            );

            $allocatedCount++;
            $missedAllocations[] = [
                'user_email' => $user->email,
                'plan' => $plan['name'],
                'credits' => $monthlyCredits,
            ];

            $this->warn("⚠️  Missed allocation detected! Added {$monthlyCredits} credits to {$user->email} ({$plan['name']})");
        }

        $this->newLine();
        $this->info("Credit reconciliation complete:");
        $this->line("  - Active subscribers checked: {$users->count()}");
        $this->line("  - Already synced (skipped): {$skippedCount}");
        $this->line("  - Missed allocations fixed: {$allocatedCount}");

        if ($allocatedCount > 0) {
            $this->newLine();
            $this->warn("WARNING: {$allocatedCount} missed allocation(s) were detected and fixed.");
            $this->warn('This indicates that some webhook events may not have been processed.');
            $this->warn('Consider investigating webhook delivery and processing logs.');

            // Log to Laravel log for monitoring
            \Illuminate\Support\Facades\Log::warning('Credit reconciliation found missed allocations', [
                'count' => $allocatedCount,
                'allocations' => $missedAllocations,
            ]);
        }

        return Command::SUCCESS;
    }
}
