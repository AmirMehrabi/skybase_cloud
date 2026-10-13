<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_usage_cycles', function (Blueprint $table): void {
            $table->json('accounting_baselines')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_usage_cycles', function (Blueprint $table): void {
            $table->dropColumn('accounting_baselines');
        });
    }
};
