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
        Schema::create('network_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('category')->default('system');
            $table->string('message');
            $table->enum('status', ['active', 'resolved'])->default('active');
            $table->timestamp('occurred_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'occurred_at']);
            $table->index(['tenant_id', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_alerts');
    }
};
