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
        if (Schema::hasColumn('routers', 'site_id')) {
            return;
        }

        Schema::table('routers', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('tenant_id')->constrained('sites')->nullOnDelete();
            $table->index(['tenant_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('routers', 'site_id')) {
            return;
        }

        Schema::table('routers', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropIndex(['tenant_id', 'site_id']);
            $table->dropColumn('site_id');
        });
    }
};
