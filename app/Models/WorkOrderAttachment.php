<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderAttachment extends Model
{
    use BelongsToTenant, HasFactory;
    use BelongsToUserGroup;

    protected $guarded = ['id'];
}
