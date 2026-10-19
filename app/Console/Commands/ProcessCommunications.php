<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\MessageDelivery;
use App\Models\Tenant;
use App\Services\CampaignService;
use App\Services\CommunicationDeliveryService;
use App\Services\NotificationIncidentService;
use App\Services\TenantContext;
use App\Services\TenantNotificationService;
use Illuminate\Console\Command;
use Throwable;

class ProcessCommunications extends Command
{
    protected $signature = 'communications:process';

    protected $description = 'Process the communication outbox, scheduled campaigns, reminders, escalations and receipts';

    public function handle(TenantContext $context): int
    {
        $failed = false;
        Tenant::query()->where('status', 'active')->each(function (Tenant $tenant) use ($context, &$failed): void {
            try {
                $context->run((string) $tenant->id, function () use ($tenant): void {
                    Campaign::query()->where('status', 'scheduled')->where('scheduled_at', '<=', now())->each(fn (Campaign $campaign) => app(CampaignService::class)->materialize($campaign));
                    app(NotificationIncidentService::class)->escalate($tenant->id);
                    $this->reminders();
                    MessageDelivery::query()->where('tenant_id', $tenant->id)->where('context->sensitive', true)->where('expires_at', '<', now())->whereNotIn('status', ['sending'])->each(function ($delivery): void {
                        $delivery->update(['content' => [], 'status' => in_array($delivery->status, ['pending', 'retrying'], true) ? 'skipped' : $delivery->status]);
                    });
                    app(CommunicationDeliveryService::class)->dispatchDue($tenant->id);
                    app(CommunicationDeliveryService::class)->refreshReceipts($tenant->id);
                    Campaign::query()->whereIn('status', ['sending', 'completed', 'completed_with_failures'])->each(fn (Campaign $campaign) => app(CampaignService::class)->finish($campaign));
                });
            } catch (Throwable $exception) {
                $failed = true;
                $this->error('Communication processing failed for tenant '.$tenant->id.': '.class_basename($exception));
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function reminders(): void
    {
        foreach ([7, 5, 3, 1, 0] as $days) {
            Invoice::query()->whereDate('due_date', now()->timezone(tenant()->timezone ?? 'UTC')->addDays($days)->toDateString())
                ->whereNotIn('status', ['paid', 'cancelled', 'void', 'draft'])->with('customer')->chunkById(100, function ($invoices) use ($days): void {
                    foreach ($invoices as $invoice) {
                        if ($invoice->customer) {
                            app(TenantNotificationService::class)->notifyCustomer($invoice->customer, 'billing.invoice_reminder_'.$days, [
                                'title' => $days === 0 ? 'Invoice due today' : 'Upcoming invoice payment',
                                'body' => 'Your invoice is due on '.$invoice->due_date->toDateString().'.',
                                'invoice_id' => $invoice->id, 'invoice_unpaid' => true, 'due_date' => $invoice->due_date->toDateString(),
                                'event_identity' => 'invoice:'.$invoice->id.':'.$invoice->due_date->toDateString().':'.$days,
                            ], $invoice);
                        }
                    }
                });
        }
    }
}
