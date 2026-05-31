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
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'name')) {
                $table->string('name')->nullable()->after('subscription_code');
            }

            if (! Schema::hasColumn('subscriptions', 'service_type')) {
                $table->string('service_type')->default('hotspot')->after('name')->index();
            }
        });

        DB::table('subscriptions')
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->whereNull('subscriptions.name')
            ->select([
                'subscriptions.id',
                'customers.first_name',
                'customers.last_name',
                'customers.name as customer_name',
                'customers.company_name',
            ])
            ->orderBy('subscriptions.id')
            ->chunkById(200, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $name = trim(implode(' ', array_filter([
                        $subscription->first_name,
                        $subscription->last_name,
                    ])));

                    DB::table('subscriptions')
                        ->where('id', $subscription->id)
                        ->update([
                            'name' => $name !== ''
                                ? $name
                                : ($subscription->customer_name ?: $subscription->company_name),
                        ]);
                }
            }, 'subscriptions.id', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'service_type')) {
                $table->dropIndex(['service_type']);
                $table->dropColumn('service_type');
            }

            if (Schema::hasColumn('subscriptions', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
