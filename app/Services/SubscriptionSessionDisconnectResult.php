<?php

namespace App\Services;

class SubscriptionSessionDisconnectResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $method = null,
        public readonly ?int $routerId = null,
        public readonly ?string $routerName = null,
        public readonly int $sessionsRemoved = 0,
    ) {}

    public static function success(string $message, ?string $method = null, ?int $routerId = null, ?string $routerName = null, int $sessionsRemoved = 0): self
    {
        return new self('success', $message, $method, $routerId, $routerName, $sessionsRemoved);
    }

    public static function skipped(string $message, ?string $method = null, ?int $routerId = null, ?string $routerName = null): self
    {
        return new self('skipped', $message, $method, $routerId, $routerName);
    }

    public static function failed(string $message, ?string $method = null, ?int $routerId = null, ?string $routerName = null): self
    {
        return new self('failed', $message, $method, $routerId, $routerName);
    }

    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function shouldAlert(): bool
    {
        return in_array($this->status, ['failed', 'skipped'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'method' => $this->method,
            'router_id' => $this->routerId,
            'router_name' => $this->routerName,
            'sessions_removed' => $this->sessionsRemoved,
        ];
    }
}
