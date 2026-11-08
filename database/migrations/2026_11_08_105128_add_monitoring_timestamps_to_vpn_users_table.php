<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_users', function (Blueprint $table): void {
            $table->timestamp('observed_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('reserved_vpn_ip', 45)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vpn_users', function (Blueprint $table): void {
            $table->dropIndex(['observed_at']);
            $table->dropColumn(['observed_at', 'last_seen_at', 'reserved_vpn_ip']);
        });
    }
};
