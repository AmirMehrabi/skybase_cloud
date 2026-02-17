<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    /** @use HasFactory<\Database\Factories\RouterFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'model',
        'vendor',
        'ip_address',
        'api_port',
        'api_username',
        'api_password',
        'ssh_port',
        'location',
        'site',
        'status',
        'version',
        'uptime',
        'cpu_usage',
        'memory_usage',
        'active_sessions_count',
        'total_customers',
        'enable_monitoring',
        'enable_provisioning',
        'timeout',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enable_monitoring' => 'boolean',
            'enable_provisioning' => 'boolean',
        ];
    }

    /**
     * Check if router is online.
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }
}
