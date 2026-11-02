<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'file_path', 'status', 'team_id'])]
class Document extends Model
{
    use HasFactory, BelongsToTeam;

    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_SCANNING = 'scanning';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_SCAN_FAILED = 'scan_failed';
    public const STATUS_QUARANTINED = 'quarantined';
    public const STATUS_FAILED = 'failed';

    protected static function booted(): void
    {
        static::saving(function (Document $document): void {
            if (! in_array($document->status, [
                self::STATUS_UPLOADED,
                self::STATUS_SCANNING,
                self::STATUS_QUEUED,
                self::STATUS_PROCESSING,
                self::STATUS_PROCESSED,
                self::STATUS_SCAN_FAILED,
                self::STATUS_QUARANTINED,
                self::STATUS_FAILED,
            ], true)) {
                $document->status = self::STATUS_UPLOADED;
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
