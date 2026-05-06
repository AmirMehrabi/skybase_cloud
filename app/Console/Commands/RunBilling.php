<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class RunBilling extends Command
{
    protected $signature = 'billing:run';

    protected $description = 'Run idempotent billing invoice generation and overdue suspension.';

    public function handle(BillingService $billing): int
    {
        $results = $billing->run();

        $this->info("Created {$results['created_invoices']} invoice(s).");
        $this->info("Marked {$results['marked_overdue']} invoice(s) overdue.");
        $this->info("Suspended {$results['suspended_subscriptions']} subscription(s).");

        return self::SUCCESS;
    }
}
