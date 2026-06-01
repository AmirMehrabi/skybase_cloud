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
        if (Schema::hasTable('notifications')) {
            if (! Schema::hasColumn('notifications', 'tenant_id')) {
                Schema::table('notifications', function (Blueprint $table): void {
                    $table->string('tenant_id')->nullable()->after('id')->index();
                });
            }

            if (! Schema::hasColumn('notifications', 'archived_at')) {
                Schema::table('notifications', function (Blueprint $table): void {
                    $table->timestamp('archived_at')->nullable()->after('read_at')->index();
                });
            }

            try {
                Schema::table('notifications', function (Blueprint $table): void {
                    $table->index(['tenant_id', 'notifiable_type', 'notifiable_id', 'read_at'], 'notifications_tenant_recipient_read_idx');
                });
            } catch (Throwable) {
            }

            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'notifiable_type', 'notifiable_id', 'read_at'], 'notifications_tenant_recipient_read_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::dropIfExists('notifications');
        }
    }
};
