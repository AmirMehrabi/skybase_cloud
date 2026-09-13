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
        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('user_group_id')
                ->nullable()
                ->after('tenant_id')
                ->index()
                ->constrained('user_groups')
                ->restrictOnDelete();
        });

        DB::table('plans')
            ->select(['id', 'tenant_id'])
            ->orderBy('id')
            ->chunkById(200, function ($plans): void {
                foreach ($plans as $plan) {
                    $groupIds = DB::table('organizations')
                        ->where('tenant_id', $plan->tenant_id)
                        ->where('default_plan_id', $plan->id)
                        ->whereNotNull('user_group_id')
                        ->distinct()
                        ->pluck('user_group_id');

                    if ($groupIds->count() === 1) {
                        DB::table('plans')
                            ->where('tenant_id', $plan->tenant_id)
                            ->where('id', $plan->id)
                            ->update(['user_group_id' => $groupIds->first()]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_group_id');
        });
    }
};
