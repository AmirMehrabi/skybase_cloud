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
        Schema::table('vpn_users', function (Blueprint $table) {
            $table->boolean('online')->default(false)->after('active');

            $table->dateTime('connected_at')->nullable()->after('online');
            $table->dateTime('disconnected_at')->nullable()->after('connected_at');

            $table->string('vpn_ip', 45)->nullable()->after('disconnected_at');
            $table->string('real_ip', 100)->nullable()->after('vpn_ip');

            $table->unsignedBigInteger('bytes_received')->default(0)->after('real_ip');
            $table->unsignedBigInteger('bytes_sent')->default(0)->after('bytes_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vpn_users', function (Blueprint $table) {
            $table->dropColumn([
                'online',
                'connected_at',
                'disconnected_at',
                'vpn_ip',
                'real_ip',
                'bytes_received',
                'bytes_sent',
            ]);
        });
    }
};
