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
        Schema::create('customer_organization', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'customer_id', 'organization_id'],
                'cust_org_tenant_customer_org_unique',
            );
            $table->index(['tenant_id', 'organization_id'], 'cust_org_tenant_org_index');
        });

        DB::table('customer_organization')->insertUsing(
            ['tenant_id', 'customer_id', 'organization_id', 'created_at', 'updated_at'],
            DB::table('customers')
                ->select([
                    'tenant_id',
                    'id',
                    'organization_id',
                    DB::raw('CURRENT_TIMESTAMP'),
                    DB::raw('CURRENT_TIMESTAMP'),
                ])
                ->whereNotNull('organization_id'),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_organization');
    }
};
