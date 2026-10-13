<?php

namespace App\Console\Commands;

use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\SubscriptionItem;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnforceTenantConstraints extends Command
{
    protected $signature = 'security:tenant-constraints {--remove : Remove the additional composite constraints}';

    protected $description = 'Check existing tenant references before explicitly installing additional billing constraints';

    public function handle(): int
    {
        $references = [
            [Organization::class, 'organizations', 'default_plan_id', 'plans'],
            [SubscriptionItem::class, 'subscription_items', 'subscription_id', 'subscriptions'],
            [SubscriptionItem::class, 'subscription_items', 'plan_id', 'plans'],
            [InvoiceItem::class, 'invoice_items', 'invoice_id', 'invoices'],
        ];
        if (! $this->option('remove')) {
            foreach ($references as [$model, $table, $column, $parent]) {
                $invalid = $model::withoutGlobalScopes()->leftJoin($parent.' as security_parent', $table.'.'.$column, '=', 'security_parent.id')
                    ->whereNotNull($table.'.'.$column)
                    ->where(function ($query) use ($table): void {
                        $query->whereNull('security_parent.id')->orWhereNull('security_parent.tenant_id')->orWhereNull($table.'.tenant_id')
                            ->orWhereColumn($table.'.tenant_id', '<>', 'security_parent.tenant_id');
                    })->count();
                if ($invalid > 0) {
                    $this->error($table.'.'.$column.' has '.$invalid.' invalid tenant references. No constraints were changed.');

                    return self::FAILURE;
                }
            }
        }
        foreach ($references as [$model, $table, $column, $parent]) {
            $columns = ['tenant_id', $column];
            $exists = Schema::hasForeignKey($table, $columns);
            if ($this->option('remove') && $exists) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($columns));
            } elseif (! $this->option('remove') && ! $exists) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns, $parent, $column): void {
                    $foreign = $blueprint->foreign($columns)->references(['tenant_id', 'id'])->on($parent);
                    if (in_array($column, ['subscription_id', 'invoice_id'], true)) {
                        $foreign->cascadeOnDelete();
                    }
                });
            }
        }
        $this->info('Additional tenant constraints '.($this->option('remove') ? 'removed.' : 'installed.'));

        return self::SUCCESS;
    }
}
