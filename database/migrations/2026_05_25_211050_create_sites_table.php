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
        if (Schema::hasTable('sites')) {
            Schema::table('sites', function (Blueprint $table) {
                if (! Schema::hasColumn('sites', 'tenant_id')) {
                    $table->string('tenant_id')->nullable();
                }

                if (! Schema::hasColumn('sites', 'code')) {
                    $table->string('code')->nullable();
                }

                if (! Schema::hasColumn('sites', 'name')) {
                    $table->string('name')->nullable();
                }

                if (! Schema::hasColumn('sites', 'description')) {
                    $table->text('description')->nullable();
                }

                if (! Schema::hasColumn('sites', 'address')) {
                    $table->string('address')->nullable();
                }

                if (! Schema::hasColumn('sites', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable();
                }

                if (! Schema::hasColumn('sites', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable();
                }

                if (! Schema::hasColumn('sites', 'status')) {
                    $table->enum('status', ['active', 'inactive'])->default('active');
                }

                if (! Schema::hasColumn('sites', 'created_at')) {
                    $table->timestamps();
                }
            });

            return;
        }

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
