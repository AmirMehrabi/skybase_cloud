<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_items', 'tenant_id')) {
            Schema::table('invoice_items', function (Blueprint $table): void {
                $table->string('tenant_id')->nullable()->index();
            });
        }
        foreach (InvoiceItem::withoutGlobalScopes()->whereNull('tenant_id')->lazyById(100) as $item) {
            $tenantId = Invoice::withoutGlobalScopes()->whereKey($item->invoice_id)->value('tenant_id');
            InvoiceItem::withoutGlobalScopes()->whereKey($item->id)->update(['tenant_id' => $tenantId]);
        }
        Schema::table('routers', function (Blueprint $table): void {
            $table->text('api_password')->nullable()->change();
            $table->text('coa_secret')->nullable()->change();
        });
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->text('pppoe_password')->nullable()->change();
        });
        Schema::table('settings', function (Blueprint $table): void {
            $table->longText('value')->nullable()->change();
        });
        if (! Schema::hasColumn('user_settings', 'tenant_id')) {
            Schema::table('user_settings', function (Blueprint $table): void {
                $table->string('tenant_id')->nullable()->index();
            });
        }
        foreach (UserSetting::withoutGlobalScopes()->whereNull('tenant_id')->lazyById(100) as $setting) {
            $tenantId = User::withoutGlobalScopes()->whereKey($setting->user_id)->value('tenant_id');
            UserSetting::withoutGlobalScopes()->whereKey($setting->id)->update(['tenant_id' => $tenantId]);
        }
        foreach (['mfa_secret', 'mfa_recovery_codes', 'mfa_confirmed_at', 'mfa_last_counter'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    match ($column) {
                        'mfa_confirmed_at' => $table->timestamp($column)->nullable(),
                        'mfa_last_counter' => $table->unsignedBigInteger($column)->nullable(),
                        default => $table->text($column)->nullable(),
                    };
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
        Schema::table('user_settings', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['mfa_secret', 'mfa_recovery_codes', 'mfa_confirmed_at', 'mfa_last_counter']);
        });
    }
};
