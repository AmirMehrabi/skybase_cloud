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
        Schema::create('organization_subscription', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'subscription_id', 'organization_id']);
            $table->index(['tenant_id', 'organization_id']);
        });

        DB::table('organization_subscription')->insertUsing(
            ['tenant_id', 'subscription_id', 'organization_id', 'created_at', 'updated_at'],
            DB::table('subscriptions')
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
        Schema::dropIfExists('organization_subscription');
    }
};
