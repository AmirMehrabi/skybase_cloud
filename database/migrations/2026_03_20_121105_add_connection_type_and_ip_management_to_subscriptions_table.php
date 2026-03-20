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
            // Connection type: pppoe, dhcp, static
            $table->enum('connection_type', ['pppoe', 'dhcp', 'static'])->default('pppoe')->after('router_id');

            // MAC address for DHCP connections
            $table->string('mac_address', 17)->nullable()->after('pppoe_password');

            // IP Pool reference for system-managed IPs
            $table->foreignId('ip_pool_id')->nullable()->constrained()->nullOnDelete()->after('ip_address');

            // IP assignment method: system (assigned from IpPool), router (router manages it), null (no IP)
            $table->enum('ip_management', ['system', 'router'])->nullable()->after('ip_pool_id');

            // Index for connection type queries
            $table->index('connection_type');
            $table->index('ip_management');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['connection_type']);
            $table->dropIndex(['ip_management']);
            $table->dropColumn(['connection_type', 'mac_address', 'ip_pool_id', 'ip_management']);
        });
    }
};
