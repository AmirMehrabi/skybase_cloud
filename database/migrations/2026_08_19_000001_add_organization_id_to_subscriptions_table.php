<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('organizations')
                ->nullOnDelete();
            $table->index(['tenant_id', 'organization_id']);
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('customers', 'organization_id')) {
            DB::table('subscriptions')
                ->join('customers', function ($join): void {
                    $join->on('customers.id', '=', 'subscriptions.customer_id')
                        ->on('customers.tenant_id', '=', 'subscriptions.tenant_id');
                })
                ->whereNull('subscriptions.organization_id')
                ->whereNotNull('customers.organization_id')
                ->update([
                    'subscriptions.organization_id' => DB::raw('customers.organization_id'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'organization_id']);
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
