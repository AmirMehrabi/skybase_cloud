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
        Schema::create('ip_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();

            // Network Configuration
            $table->string('network_address');
            $table->unsignedTinyInteger('cidr')->default(24);
            $table->string('gateway')->nullable();
            $table->string('dns_primary')->nullable();
            $table->string('dns_secondary')->nullable();
            $table->unsignedInteger('vlan_id')->nullable();

            // Pool Settings
            $table->enum('type', ['dynamic', 'static', 'mixed'])->default('mixed');
            $table->enum('status', ['active', 'disabled', 'exhausted'])->default('active');

            // Advanced Settings
            $table->boolean('allow_static')->default(true);
            $table->boolean('auto_assign')->default(true);
            $table->boolean('block_reserved')->default(false);

            // Location
            $table->string('site')->nullable();

            // Statistics (computed, can be updated by events)
            $table->unsignedInteger('total_ips')->default(0);
            $table->unsignedInteger('used_ips')->default(0);
            $table->unsignedInteger('reserved_ips')->default(0);
            $table->unsignedInteger('available_ips')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'router_id']);
            $table->unique(['tenant_id', 'network_address', 'cidr'], 'unique_pool_per_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_pools');
    }
};
