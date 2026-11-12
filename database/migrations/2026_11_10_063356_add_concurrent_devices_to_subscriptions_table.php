<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('concurrent_devices')->nullable();
            $table->string('ip_allocation', 24)->nullable();
            $table->json('reserved_ip_addresses')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['concurrent_devices', 'ip_allocation', 'reserved_ip_addresses']);
        });
    }
};
