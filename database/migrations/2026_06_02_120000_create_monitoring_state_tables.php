<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('router_monitoring_states', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->enum('status', ['online', 'offline', 'warning'])->default('offline');
            $table->decimal('latency_ms', 10, 2)->nullable();
            $table->decimal('packet_loss_percent', 5, 2)->nullable();
            $table->string('uptime')->nullable();
            $table->unsignedTinyInteger('cpu_usage')->nullable();
            $table->unsignedTinyInteger('memory_usage')->nullable();
            $table->unsignedInteger('active_sessions_count')->nullable();
            $table->timestamp('sampled_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'router_id'], 'router_monitoring_state_unique');
            $table->index(['tenant_id', 'status', 'sampled_at'], 'router_monitoring_state_status_idx');
        });

        Schema::create('subscription_bandwidth_states', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->string('interface_name')->nullable();
            $table->unsignedBigInteger('rx_bps')->default(0);
            $table->unsignedBigInteger('tx_bps')->default(0);
            $table->string('source')->default('routeros');
            $table->timestamp('sampled_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'subscription_id'], 'subscription_bandwidth_state_unique');
            $table->index(['tenant_id', 'router_id', 'sampled_at'], 'subscription_bandwidth_state_router_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_bandwidth_states');
        Schema::dropIfExists('router_monitoring_states');
    }
};
