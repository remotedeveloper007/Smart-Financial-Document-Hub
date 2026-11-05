<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'role'])]
class PlatformAdministrator extends Model
{
    public const ROLE_SYSTEM_OWNER = 'system_owner';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
