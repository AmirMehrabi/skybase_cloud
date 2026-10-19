<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('event_key')->nullable();
            $table->string('recipient_type')->default('customer');
            $table->boolean('enabled')->default(true);
            $table->json('channels');
            $table->string('subject')->nullable();
            $table->longText('email_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->string('sms_mode')->default('plain');
            $table->string('lookup_template')->nullable();
            $table->json('lookup_tokens')->nullable();
            $table->json('recipients')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['tenant_id', 'event_key']);
        });
        Schema::create('communication_audiences', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->json('filters');
            $table->timestamps();
        });
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->string('purpose')->default('service');
            $table->json('channels');
            $table->json('content');
            $table->json('audience_filters');
            $table->string('audience_mode')->default('snapshot');
            $table->json('recipient_ids');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('materialized_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'scheduled_at']);
        });
        Schema::create('notification_incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('observing');
            $table->unsignedInteger('failures')->default(0);
            $table->unsignedInteger('successes')->default(0);
            $table->unsignedInteger('stage')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('message_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('notification_incident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_key')->nullable();
            $table->string('idempotency_key', 64);
            $table->string('recipient_type')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('channel');
            $table->text('destination')->nullable();
            $table->longText('content');
            $table->json('context')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('receipt_checked_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'idempotency_key'], 'delivery_tenant_idempotency_unique');
            $table->index(['tenant_id', 'status', 'available_at'], 'delivery_due_index');
        });
    }

    public function down(): void
    {
        foreach (['message_deliveries', 'notification_incidents', 'campaigns', 'communication_audiences', 'communication_templates'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
