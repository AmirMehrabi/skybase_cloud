<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'company_name',
        'email',
        'phone',
        'country',
        'timezone',
        'status',
        'plan_id',
        'trial_ends_at',
        'tagline',
        'business_license',
        'tax_id',
        'website_url',
        'support_phone',
        'address',
        'city',
        'state',
        'zip',
        'date_format',
        'time_format',
        'first_day_of_week',
        'currency',
        'currency_symbol_position',
        'thousands_separator',
        'decimal_separator',
        'locale',
        'maintenance_mode',
        'custom_domain',
        'primary_color',
        'secondary_color',
        'accent_color',
        'dark_mode_enabled',
        'custom_css',
        'company_logo',
        'company_logo_dark',
        'favicon',
        'login_logo',
        'email_header_logo',
        'email_footer_logo',
        'invoice_logo',
        'login_background',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'dark_mode_enabled' => 'boolean',
        'maintenance_mode' => 'boolean',
    ];

    public const BRANDING_ASSETS = [
        'company_logo',
        'company_logo_dark',
        'favicon',
        'login_logo',
        'email_header_logo',
        'email_footer_logo',
        'invoice_logo',
        'login_background',
    ];

    public function brandingAssetUrl(string $asset): ?string
    {
        if (! in_array($asset, self::BRANDING_ASSETS, true)) {
            return null;
        }

        $path = $this->getAttribute($asset);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return route('branding.asset', ['asset' => $asset, 'v' => $this->updated_at?->timestamp]);
    }

    public function brandName(): string
    {
        return filled($this->company_name) ? $this->company_name : 'SkyBase Cloud';
    }

    public function brandTagline(): string
    {
        return filled($this->tagline) ? $this->tagline : 'Complete ISP Management Platform';
    }

    public function navbarLogoUrl(): string
    {
        return $this->brandingAssetUrl('company_logo_dark')
            ?? $this->brandingAssetUrl('company_logo')
            ?? asset('assets/images/logo/logo-black.png');
    }

    public function faviconUrl(): string
    {
        return $this->brandingAssetUrl('favicon') ?? asset('favicon.ico');
    }

    public function invoiceLogoUrl(): string
    {
        return $this->brandingAssetUrl('invoice_logo')
            ?? $this->brandingAssetUrl('company_logo')
            ?? asset('assets/images/logo/logo-black-big.png');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasExpiredTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }
}
