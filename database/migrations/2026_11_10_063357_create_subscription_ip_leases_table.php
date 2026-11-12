<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_ip_leases', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ip_address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->uuid('token')->unique();
            $table->string('session_key', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamp('pending_until')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'router_id', 'session_key'], 'subscription_ip_leases_session_unique');
            $table->unique(['tenant_id', 'ip_address_id'], 'subscription_ip_leases_address_unique');
            $table->index(['tenant_id', 'subscription_id', 'released_at'], 'subscription_ip_leases_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_ip_leases');
    }
};
