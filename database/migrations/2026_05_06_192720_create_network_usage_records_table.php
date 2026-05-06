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
        Schema::create('network_usage_records', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->unsignedBigInteger('download_bytes')->default(0);
            $table->unsignedBigInteger('upload_bytes')->default(0);
            $table->unsignedInteger('session_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'router_id']);
            $table->index(['tenant_id', 'last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_usage_records');
    }
};
