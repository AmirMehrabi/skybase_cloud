<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SecurityEventLogger
{
    /** @param array<string, mixed> $context */
    public function record(string $event, array $context = []): void
    {
        Log::channel('security')->notice($event, array_intersect_key($context, array_flip([
            'user_id', 'tenant_id', 'ip', 'route', 'resource_id', 'guard', 'reason',
        ])));
    }
}
