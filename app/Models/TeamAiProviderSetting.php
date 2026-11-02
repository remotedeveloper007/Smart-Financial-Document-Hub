<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;

#[Fillable(['team_id', 'provider', 'model', 'credentials'])]
class TeamAiProviderSetting extends Model
{
    use BelongsToTeam;

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials' => AsEncryptedArrayObject::class,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
