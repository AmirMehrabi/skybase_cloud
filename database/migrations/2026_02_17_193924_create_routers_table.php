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
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('vendor')->default('Mikrotik');
            $table->string('ip_address')->unique();
            $table->integer('api_port')->default(8728);
            $table->string('api_username')->nullable();
            $table->string('api_password')->nullable();
            $table->integer('ssh_port')->default(22);
            $table->string('location')->nullable();
            $table->string('site')->nullable();
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->string('version')->nullable();
            $table->string('uptime')->nullable();
            $table->integer('cpu_usage')->default(0);
            $table->integer('memory_usage')->default(0);
            $table->integer('active_sessions_count')->default(0);
            $table->integer('total_customers')->default(0);
            $table->boolean('enable_monitoring')->default(true);
            $table->boolean('enable_provisioning')->default(true);
            $table->integer('timeout')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
