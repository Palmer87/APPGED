<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class ProcessExpiringSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-expiring-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expiring trials, expired subscriptions, and dispatch notifications';

    /**
     * Execute the console command.
     */
    public function handle(BillingService $billingService): int
    {
        $this->info('Processing expiring trials and subscriptions...');

        $results = $billingService->processExpiringSubscriptions();

        $this->info(sprintf(
            'Completed successfully: %d subscription(s) marked as expired, %d trial expiration warning(s) dispatched.',
            $results['expired_count'],
            $results['trial_warnings_count']
        ));

        return Command::SUCCESS;
    }
}
