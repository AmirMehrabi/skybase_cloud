<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class GenerateDueInvoices extends Command
{
    protected $signature = 'billing:generate-invoices';

    protected $description = 'Generate all due subscription invoices without queue dependency.';

    public function handle(BillingService $billing): int
    {
        $created = $billing->generateDueInvoices();

        $this->info("Created {$created} due invoice(s).");

        return self::SUCCESS;
    }
}
