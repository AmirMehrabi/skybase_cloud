<?php

namespace App\Models\Concerns;

use App\Models\Scopes\UserGroupScope;
use App\Models\UserGroup;
use App\Services\UserGroupAssignmentService;
use App\Support\UserGroups\UserGroupContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUserGroup
{
    protected static function bootBelongsToUserGroup(): void
    {
        static::addGlobalScope(new UserGroupScope);

        static::creating(function (Model $model): void {
            $inherited = app(UserGroupAssignmentService::class)->inheritedGroup($model);

            if ($inherited['resolved']) {
                $model->setAttribute('user_group_id', $inherited['group_id']);

                return;
            }

            $context = app(UserGroupContext::class);

            if ($model->getAttribute('user_group_id') === null && $context->shouldScope()) {
                $model->setAttribute('user_group_id', $context->groupId());
            }
        });
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
