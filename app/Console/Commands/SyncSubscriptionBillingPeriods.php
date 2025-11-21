<?php

namespace App\Console\Commands;

use App\Domain\Users\Models\User;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;

class SyncSubscriptionBillingPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:sync-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync billing periods from Stripe to local database for existing subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing billing periods for existing subscriptions...');

        $subscriptions = Subscription::whereNull('current_period_end')
            ->orWhereNull('current_period_start')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('All subscriptions already have billing periods synced!');
            return 0;
        }

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        $synced = 0;
        $errors = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $stripeSubscription = $subscription->asStripeSubscription();

                // Access as array or use offsetGet
                $currentPeriodStart = $stripeSubscription['current_period_start'] ?? null;
                $currentPeriodEnd = $stripeSubscription['current_period_end'] ?? null;

                if ($currentPeriodStart && $currentPeriodEnd) {
                    $subscription->current_period_start = date('Y-m-d H:i:s', $currentPeriodStart);
                    $subscription->current_period_end = date('Y-m-d H:i:s', $currentPeriodEnd);
                    $subscription->save();
                    $synced++;
                }
            } catch (\Exception $e) {
                $this->error("\nFailed to sync subscription {$subscription->id}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info("Synced {$synced} subscriptions successfully.");

        if ($errors > 0) {
            $this->warn("{$errors} subscriptions failed to sync.");
        }

        return 0;
    }
}
