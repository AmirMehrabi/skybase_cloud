<?php

namespace App\Jobs;

use App\Models\MessageDelivery;
use App\Services\CommunicationDeliveryService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverCommunication implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return $this->tenantId.':'.$this->deliveryId;
    }

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public string $tenantId, public int $deliveryId) {}

    public function handle(TenantContext $context, CommunicationDeliveryService $service): void
    {
        $context->run($this->tenantId, function () use ($service): void {
            $delivery = MessageDelivery::query()->where('tenant_id', $this->tenantId)->find($this->deliveryId);
            if ($delivery) {
                $service->deliver($delivery);
            }
        });
    }
}
