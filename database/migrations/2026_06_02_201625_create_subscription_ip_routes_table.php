<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_ip_routes', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ip_pool_id')->constrained('ip_pools')->cascadeOnDelete();
            $table->foreignId('ip_address_id')->nullable()->constrained('ip_addresses')->nullOnDelete();
            $table->string('ip_address');
            $table->unsignedTinyInteger('cidr')->default(32);
            $table->string('routeros_route_id')->nullable();
            $table->string('routeros_comment')->nullable();
            $table->enum('routeros_sync_status', ['pending', 'synced', 'skipped', 'failed'])->default('pending');
            $table->text('routeros_sync_error')->nullable();
            $table->timestamp('routeros_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'subscription_id', 'ip_address', 'cidr'], 'subscription_ip_routes_unique_destination');
            $table->index(['tenant_id', 'subscription_id']);
            $table->index(['tenant_id', 'routeros_sync_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_ip_routes');
    }
};
