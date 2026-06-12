<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_points', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('vendor');
            $table->string('mac_address')->unique();
            $table->string('ip_address')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('frequency_band')->nullable();
            $table->string('channel')->nullable();
            $table->string('ssid')->nullable();
            $table->integer('tx_power')->nullable();
            $table->string('antenna_type')->nullable();
            $table->integer('antenna_gain')->nullable();
            $table->decimal('height_meters', 5, 2)->nullable();
            $table->integer('azimuth')->nullable();
            $table->integer('coverage_angle')->nullable();
            $table->integer('max_clients')->default(0);
            $table->integer('connected_clients')->default(0);
            $table->enum('status', ['online', 'offline', 'maintenance', 'decommissioned'])->default('offline');
            $table->timestamp('last_status_checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_points');
    }
};
