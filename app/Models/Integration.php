<?php

namespace App\Models;

use App\Models\Team;
use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'provider', 'external_id', 'credentials', 'status'])]
class Integration extends Model
{
    use HasFactory, BelongsToTeam;

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials' => AsEncryptedArrayObject::class,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
