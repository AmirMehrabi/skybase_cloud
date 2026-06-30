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
        Schema::table('subscription_bandwidth_states', function (Blueprint $table) {
            $table->unsignedBigInteger('last_download_bytes')->nullable()->after('tx_bps');
            $table->unsignedBigInteger('last_upload_bytes')->nullable()->after('last_download_bytes');
            $table->timestamp('counter_sampled_at')->nullable()->after('last_upload_bytes');
            $table->timestamp('last_success_at')->nullable()->after('sampled_at');
            $table->unsignedInteger('consecutive_failures')->default(0)->after('last_success_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_bandwidth_states', function (Blueprint $table) {
            $table->dropColumn([
                'last_download_bytes',
                'last_upload_bytes',
                'counter_sampled_at',
                'last_success_at',
                'consecutive_failures',
            ]);
        });
    }
};
