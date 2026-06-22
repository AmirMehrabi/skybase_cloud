<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:mark-corporate-customers {--dry-run : Preview changes without updating the database}]')]
#[Description('Mark customers with an organization as corporate (business) customers')]
class MarkCorporateCustomers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $customers = Customer::whereNotNull('organization_id')
            ->where('customer_type', '!=', 'business')
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No customers with organizations found that need updating.');

            return self::SUCCESS;
        }

        $this->info("Found {$customers->count()} customer(s) with organizations to mark as corporate.");

        if ($dryRun) {
            $this->newLine();
            $this->info('DRY RUN — No changes will be made.');
            $this->newLine();

            $this->table(
                ['ID', 'Name', 'Email', 'Current Type', 'Organization ID'],
                $customers->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->full_name ?? $c->name,
                    'email' => $c->email,
                    'customer_type' => $c->customer_type,
                    'organization_id' => $c->organization_id,
                ])
            );

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        foreach ($customers as $customer) {
            $customer->update(['customer_type' => 'business']);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully marked {$customers->count()} customer(s) as corporate.");

        return self::SUCCESS;
    }
}
