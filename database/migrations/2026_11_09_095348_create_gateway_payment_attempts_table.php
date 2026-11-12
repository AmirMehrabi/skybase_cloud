<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('invoices', ['tenant_id', 'id'], 'unique')) {
            Schema::table('invoices', fn (Blueprint $table) => $table->unique(['tenant_id', 'id']));
        }
        if (! Schema::hasTable('gateway_payment_attempts')) {
            Schema::create('gateway_payment_attempts', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('invoice_id');
                $table->uuid('reference')->unique();
                $table->string('environment');
                $table->string('currency', 3)->default('ZAR');
                $table->unsignedBigInteger('amount_minor');
                $table->string('method')->nullable();
                $table->string('status')->default('pending');
                $table->text('credentials');
                $table->string('provider_reference')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'invoice_id']);
                $table->unique(['environment', 'provider_reference']);

            });
        }
        if (! Schema::hasIndex('gateway_payment_attempts', ['reference'], 'unique')) {
            Schema::table('gateway_payment_attempts', fn (Blueprint $table) => $table->unique('reference'));
        }
        if (! collect(Schema::getForeignKeys('gateway_payment_attempts'))->contains(fn (array $key): bool => $key['columns'] === ['tenant_id', 'invoice_id'])) {
            Schema::table('gateway_payment_attempts', fn (Blueprint $table) => $table->foreign(['tenant_id', 'invoice_id'])->references(['tenant_id', 'id'])->on('invoices'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_payment_attempts');
    }
};
