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
        Schema::create('network_bandwidth_samples', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('interface_name')->default('uplink');
            $table->unsignedBigInteger('download_bps')->default(0);
            $table->unsignedBigInteger('upload_bps')->default(0);
            $table->unsignedBigInteger('capacity_bps')->default(1000000000);
            $table->timestamp('sampled_at');
            $table->timestamps();

            $table->index(['tenant_id', 'router_id', 'sampled_at']);
            $table->index(['tenant_id', 'interface_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_bandwidth_samples');
    }
};
