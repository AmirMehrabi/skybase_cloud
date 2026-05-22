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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('connection_status', ['online', 'offline'])
                ->nullable()
                ->after('status');
            $table->timestamp('connection_status_checked_at')
                ->nullable()
                ->after('connection_status');
            $table->index(['tenant_id', 'connection_status'], 'subscriptions_tenant_connection_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_tenant_connection_status_index');
            $table->dropColumn(['connection_status', 'connection_status_checked_at']);
        });
    }
};
