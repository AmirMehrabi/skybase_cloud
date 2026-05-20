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
        Schema::table('routers', function (Blueprint $table) {
            $table->boolean('netflow_enabled')->default(false)->after('enable_provisioning');
            $table->string('netflow_collector_host')->nullable()->after('netflow_enabled');
            $table->unsignedSmallInteger('netflow_collector_port')->default(2055)->after('netflow_collector_host');
            $table->unsignedTinyInteger('netflow_version')->default(9)->after('netflow_collector_port');
            $table->string('netflow_interfaces')->default('all')->after('netflow_version');
            $table->unsignedInteger('netflow_sampling_interval')->default(1)->after('netflow_interfaces');
            $table->string('netflow_setup_status')->nullable()->after('netflow_sampling_interval');
            $table->string('netflow_test_status')->nullable()->after('netflow_setup_status');
            $table->timestamp('netflow_last_setup_at')->nullable()->after('netflow_test_status');
            $table->timestamp('netflow_last_tested_at')->nullable()->after('netflow_last_setup_at');
            $table->timestamp('netflow_last_packet_at')->nullable()->after('netflow_last_tested_at');
            $table->text('netflow_error')->nullable()->after('netflow_last_packet_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn([
                'netflow_enabled',
                'netflow_collector_host',
                'netflow_collector_port',
                'netflow_version',
                'netflow_interfaces',
                'netflow_sampling_interval',
                'netflow_setup_status',
                'netflow_test_status',
                'netflow_last_setup_at',
                'netflow_last_tested_at',
                'netflow_last_packet_at',
                'netflow_error',
            ]);
        });
    }
};
