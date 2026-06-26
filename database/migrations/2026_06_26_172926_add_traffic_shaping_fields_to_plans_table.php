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
        Schema::table('plans', function (Blueprint $table) {
            $table->string('shaping_mode')->default('basic')->after('bandwidth_unit');
            $table->unsignedInteger('burst_threshold_download')->nullable()->after('burst_upload');
            $table->unsignedInteger('burst_threshold_upload')->nullable()->after('burst_threshold_download');
            $table->unsignedInteger('burst_time_download')->nullable()->after('burst_threshold_upload');
            $table->unsignedInteger('burst_time_upload')->nullable()->after('burst_time_download');
            $table->unsignedInteger('min_download_speed')->nullable()->after('burst_time_upload');
            $table->unsignedInteger('min_upload_speed')->nullable()->after('min_download_speed');
            $table->unsignedTinyInteger('shaping_priority')->nullable()->after('min_upload_speed');
            $table->string('queue_type')->nullable()->after('shaping_priority');
            $table->string('data_cap_action')->default('none')->after('data_unit');
            $table->unsignedInteger('throttle_download_speed')->nullable()->after('data_cap_action');
            $table->unsignedInteger('throttle_upload_speed')->nullable()->after('throttle_download_speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'shaping_mode',
                'burst_threshold_download',
                'burst_threshold_upload',
                'burst_time_download',
                'burst_time_upload',
                'min_download_speed',
                'min_upload_speed',
                'shaping_priority',
                'queue_type',
                'data_cap_action',
                'throttle_download_speed',
                'throttle_upload_speed',
            ]);
        });
    }
};
