<?php

namespace App\Console\Commands;

use App\Domain\Billing\Services\PlanService;
use App\Domain\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPlanChangeReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:send-plan-change-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to users with upcoming plan changes (within 3 days)';

    public function __construct(
        protected PlanService $planService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for upcoming plan changes...');

        // Find users with scheduled plan changes in the next 3 days
        $users = User::whereNotNull('scheduled_plan_key')
            ->whereNotNull('scheduled_plan_date')
            ->whereBetween('scheduled_plan_date', [
                now(),
                now()->addDays(3),
            ])
            ->get();

        if ($users->isEmpty()) {
            $this->info('No upcoming plan changes found.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($users as $user) {
            // Check if we've already sent a reminder recently (within last 24 hours)
            $lastReminder = \App\Domain\Billing\Models\CreditTransaction::query()
                ->where('user_id', $user->id)
                ->where('metadata->plan_change_reminder', true)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if ($lastReminder) {
                $this->line("Skipping {$user->email} - reminder already sent in last 24 hours");
                continue;
            }

            $currentPlan = $this->planService->getPlan($user->current_plan_key);
            $newPlan = $this->planService->getPlan($user->scheduled_plan_key);

            if (!$currentPlan || !$newPlan) {
                continue;
            }

            $isDowngrade = ($newPlan['monthly_credits'] ?? 0) < ($currentPlan['monthly_credits'] ?? 0);
            $daysUntilChange = now()->diffInDays($user->scheduled_plan_date, false);

            $billingUrl = route('settings.index') . '#billing';

            Mail::send('emails.plan-change-reminder', [
                'userName' => $user->name,
                'currentPlanName' => $currentPlan['name'],
                'newPlanName' => $newPlan['name'],
                'scheduledDate' => $user->scheduled_plan_date->format('F j, Y'),
                'daysUntilChange' => max(0, (int) ceil($daysUntilChange)),
                'isDowngrade' => $isDowngrade,
                'currentCredits' => $currentPlan['monthly_credits'] ?? 0,
                'newCredits' => $newPlan['monthly_credits'] ?? 0,
                'billingUrl' => $billingUrl,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Reminder: Your Plan Will Change Soon');
            });

            // Create a dummy transaction record to track that we sent the reminder
            // This prevents sending duplicate reminders
            \App\Domain\Billing\Models\CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => 0,
                'type' => 'system',
                'description' => 'Plan change reminder sent',
                'metadata' => ['plan_change_reminder' => true],
            ]);

            $sentCount++;
            $this->line("✓ Sent reminder to {$user->email} ({$currentPlan['name']} → {$newPlan['name']} in {$daysUntilChange} days)");
        }

        $this->newLine();
        $this->info("Plan change reminders sent: {$sentCount}");

        return Command::SUCCESS;
    }
}
