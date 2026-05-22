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
            $table->timestamp('last_status_checked_at')->nullable()->after('status');
            $table->timestamp('last_status_changed_at')->nullable()->after('last_status_checked_at');
            $table->text('status_check_error')->nullable()->after('last_status_changed_at');
            $table->index(['tenant_id', 'enable_monitoring', 'last_status_checked_at'], 'routers_status_check_due_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropIndex('routers_status_check_due_index');
            $table->dropColumn([
                'last_status_checked_at',
                'last_status_changed_at',
                'status_check_error',
            ]);
        });
    }
};
