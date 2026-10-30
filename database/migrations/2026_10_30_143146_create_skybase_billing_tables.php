<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index();
            $table->boolean('application_access_suspended')->default(false)->index();
        });
        Schema::create('skybase_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->string('package_key')->nullable();
            $table->string('status')->default('unconfigured')->index();
            $table->unsignedBigInteger('monthly_price_minor')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('subscriber_limit')->nullable();
            $table->json('features')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('paid_through')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamps();
        });
        Schema::create('skybase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->string('package_key');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->unsignedInteger('subscriber_limit');
            $table->json('features');
            $table->string('status')->default('pending');
            $table->boolean('simulated')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('skybase_payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreignId('skybase_invoice_id')->constrained()->restrictOnDelete();
            $table->string('authority', 64)->unique();
            $table->string('status')->default('pending');
            $table->string('reference')->nullable()->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        foreach (['tenant_admin_events', 'tenant_notes'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->string('tenant_id')->index();
                $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
                $table->unsignedBigInteger('actor_id')->nullable();
                if ($name === 'tenant_admin_events') {
                    $table->string('event');
                    $table->text('reason');
                    $table->json('changes')->nullable();
                } else {
                    $table->text('body');
                }
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['tenant_notes', 'tenant_admin_events', 'skybase_payment_attempts', 'skybase_invoices', 'skybase_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn(['archived_at', 'application_access_suspended']));
    }
};
