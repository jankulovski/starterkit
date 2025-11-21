<?php

namespace App\Console\Commands;

use App\Domain\Billing\Services\BillingMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorBillingHealth extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:monitor
                          {--check=all : Specific check to run (all|allocations|health)}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor billing system health and detect issues';

    public function __construct(
        protected BillingMonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $check = $this->option('check');

        $this->info('🔍 Running billing health checks...');
        $this->newLine();

        switch ($check) {
            case 'allocations':
                $this->checkMissedAllocations();
                break;

            case 'health':
                $this->checkSystemHealth();
                break;

            case 'all':
            default:
                $this->checkMissedAllocations();
                $this->checkSystemHealth();
                $this->displayDailyMetrics();
                break;
        }

        return Command::SUCCESS;
    }

    protected function checkMissedAllocations(): void
    {
        $this->info('📊 Checking for missed credit allocations...');

        $missedAllocations = $this->monitoringService->checkMissedAllocations();

        if (count($missedAllocations) === 0) {
            $this->components->info('✓ No missed allocations detected');
        } else {
            $this->components->error('✗ Found ' . count($missedAllocations) . ' missed allocations');

            $this->table(
                ['User ID', 'Email', 'Plan', 'Balance'],
                array_map(fn($m) => [
                    $m['user_id'],
                    $m['user_email'],
                    $m['plan'],
                    $m['credits_balance'],
                ], $missedAllocations)
            );

            Log::channel('billing')->error('Missed allocations detected', [
                'count' => count($missedAllocations),
                'users' => $missedAllocations,
            ]);
        }

        $this->newLine();
    }

    protected function checkSystemHealth(): void
    {
        $this->info('🏥 Checking system health...');

        $health = $this->monitoringService->getHealthMetrics();

        $this->newLine();
        $this->line('Overall Status: <fg=green>' . strtoupper($health['status']) . '</>');
        $this->newLine();

        $this->table(
            ['Component', 'Status', 'Details'],
            [
                [
                    'Webhooks',
                    $this->formatStatus($health['checks']['webhooks']['status']),
                    'Processed: ' . $health['checks']['webhooks']['processed_24h'] . ' (24h)',
                ],
                [
                    'Payments',
                    $this->formatStatus($health['checks']['payments']['status']),
                    'Failures: ' . $health['checks']['payments']['failures_24h'] . ' (24h)',
                ],
                [
                    'Refunds',
                    $this->formatStatus($health['checks']['refunds']['status']),
                    'Processed: ' . $health['checks']['refunds']['processed_24h'] . ' (24h)',
                ],
                [
                    'Disputes',
                    $this->formatStatus($health['checks']['disputes']['status']),
                    'Created: ' . $health['checks']['disputes']['created_24h'] . ' (24h)',
                ],
            ]
        );

        // Alert if any critical issues
        foreach ($health['checks'] as $component => $check) {
            if ($check['status'] === 'critical') {
                $this->components->error("⚠️  Critical issue detected in {$component}");
            } elseif ($check['status'] === 'warning') {
                $this->components->warn("⚠️  Warning in {$component}");
            }
        }

        $this->newLine();
    }

    protected function displayDailyMetrics(): void
    {
        $this->info('📈 Daily Metrics (' . now()->format('Y-m-d') . ')');

        $metrics = $this->monitoringService->getDailyMetrics();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Credit Usage', number_format($metrics['credit_usage']['total'])],
                ['Payment Failures', $metrics['payment_failures']],
                ['Webhooks Processed', $metrics['webhooks_processed']],
                ['Refunds Processed', $metrics['refunds_processed']],
                ['Disputes Created', $metrics['disputes_created']],
            ]
        );

        $this->newLine();
        $this->info('Credit Usage by Operation:');
        foreach ($metrics['credit_usage']['by_operation'] as $operation => $amount) {
            if ($amount > 0) {
                $this->line("  {$operation}: " . number_format($amount));
            }
        }

        $this->newLine();
    }

    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'healthy' => '<fg=green>✓ HEALTHY</>',
            'warning' => '<fg=yellow>⚠ WARNING</>',
            'critical' => '<fg=red>✗ CRITICAL</>',
            default => $status,
        };
    }
}
