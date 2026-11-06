<?php

namespace App\Models;

use App\Models\Team;
use App\Models\User;
use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'user_id', 'provider', 'model', 'input', 'response', 'status', 'error_message'])]
class AiRequest extends Model
{
    use HasFactory, BelongsToTeam;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $casts = [
        'input' => 'encrypted',
        'response' => AsArrayObject::class,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
