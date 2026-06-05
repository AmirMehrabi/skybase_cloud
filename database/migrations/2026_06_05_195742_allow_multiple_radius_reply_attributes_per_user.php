<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        try {
            Schema::table('radreply', function (Blueprint $table): void {
                $table->dropUnique(['tenant_id', 'username', 'attribute']);
            });
        } catch (Throwable) {
        }

        try {
            Schema::table('radreply', function (Blueprint $table): void {
                $table->index(['tenant_id', 'username', 'attribute']);
            });
        } catch (Throwable) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        try {
            Schema::table('radreply', function (Blueprint $table): void {
                $table->dropIndex(['tenant_id', 'username', 'attribute']);
            });
        } catch (Throwable) {
        }

        Schema::table('radreply', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'username', 'attribute']);
        });
    }
};
