<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('billing_enabled')->default(true)->after('billing_type');
            $table->timestamp('billing_disabled_at')->nullable()->after('billing_enabled');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('grace_period_days')->default(7)->after('billing_cycle');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('billing_enabled')->default(true)->after('billing_cycle');
            $table->unsignedSmallInteger('grace_period_days')->nullable()->after('billing_enabled');
            $table->date('next_billing_date')->nullable()->after('grace_period_days');
            $table->timestamp('last_billed_at')->nullable()->after('next_billing_date');
            $table->timestamp('billing_disabled_at')->nullable()->after('last_billed_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'billing_enabled',
                'grace_period_days',
                'next_billing_date',
                'last_billed_at',
                'billing_disabled_at',
            ]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('grace_period_days');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['billing_enabled', 'billing_disabled_at']);
        });
    }
};
