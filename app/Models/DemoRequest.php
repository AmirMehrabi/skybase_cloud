<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_plan',
        'business_name',
        'contact_name',
        'email',
        'phone',
        'country',
        'company_website',
        'customer_count',
        'current_system',
        'deployment_timeline',
        'message',
        'source_page',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'customer_count' => 'integer',
        ];
    }
}
