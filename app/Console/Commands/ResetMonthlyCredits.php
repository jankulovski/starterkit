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
    protected $description = 'Reset and add monthly credits for all active subscribers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting monthly credit reset...');

        $users = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active')
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                });
        })->get();

        $count = 0;

        foreach ($users as $user) {
            $plan = $user->currentPlan();
            if (! $plan) {
                continue;
            }

            $monthlyCredits = $plan['monthly_credits'] ?? 0;

            if ($monthlyCredits > 0) {
                $user->addCredits(
                    $monthlyCredits,
                    'subscription',
                    "Monthly credits for {$plan['name']} plan"
                );
                $count++;
                $this->line("Added {$monthlyCredits} credits to {$user->email} ({$plan['name']})");
            }
        }

        $this->info("Monthly credit reset complete. Updated {$count} users.");

        return Command::SUCCESS;
    }
}
