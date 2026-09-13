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
        Schema::create('site_user_group', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_group_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'site_id', 'user_group_id'], 'site_group_tenant_site_group_unique');
            $table->index(['tenant_id', 'user_group_id'], 'site_group_tenant_group_index');
        });

        DB::table('site_user_group')->insertUsing(
            ['tenant_id', 'site_id', 'user_group_id', 'created_at', 'updated_at'],
            DB::table('sites')
                ->select([
                    'tenant_id',
                    'id',
                    'user_group_id',
                    DB::raw('CURRENT_TIMESTAMP'),
                    DB::raw('CURRENT_TIMESTAMP'),
                ])
                ->whereNotNull('user_group_id'),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_user_group');
    }
};
