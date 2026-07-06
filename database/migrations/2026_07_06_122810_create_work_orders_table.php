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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('work_order_number');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('parent_work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_team_id')->nullable()->constrained('ticket_teams')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('source')->default('manual');
            $table->string('priority')->default('normal');
            $table->string('status')->default('draft');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('service_address_line1');
            $table->string('service_address_line2')->nullable();
            $table->string('service_city')->nullable();
            $table->string('service_state')->nullable();
            $table->string('service_postal_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('connection_type')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('promised_at')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'work_order_number']);
            $table->index(['tenant_id', 'status', 'priority']);
            $table->index(['tenant_id', 'scheduled_start_at']);
            $table->index(['tenant_id', 'assigned_team_id', 'assigned_user_id']);
            $table->index(['tenant_id', 'customer_id', 'created_at']);
            $table->index(['tenant_id', 'subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
