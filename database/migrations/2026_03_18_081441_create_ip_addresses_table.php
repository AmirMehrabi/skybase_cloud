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
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->foreignId('ip_pool_id')->constrained('ip_pools')->cascadeOnDelete();

            // IP Address
            $table->string('ip_address');
            $table->unique(['ip_pool_id', 'ip_address'], 'unique_ip_in_pool');

            // Assignment Info
            $table->enum('status', ['available', 'assigned', 'reserved', 'blocked'])->default('available');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Network details
            $table->string('mac_address')->nullable();
            $table->string('subscription_code')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();

            // Additional info
            $table->string('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->index(['tenant_id', 'status']);
            $table->index(['ip_pool_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
