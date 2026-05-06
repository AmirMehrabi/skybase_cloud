<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class SuspendOverdueSubscriptions extends Command
{
    protected $signature = 'billing:suspend-overdue';

    protected $description = 'Mark overdue invoices and suspend subscriptions past their invoice due date.';

    public function handle(BillingService $billing): int
    {
        $markedOverdue = $billing->markOverdueInvoices();
        $suspended = $billing->suspendOverdueSubscriptions();

        $this->info("Marked {$markedOverdue} invoice(s) overdue and suspended {$suspended} subscription(s).");

        return self::SUCCESS;
    }
}
