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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('plan')->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->after('router')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['router_id']);
            $table->dropColumn(['plan_id', 'router_id']);
        });
    }
};
