<?php

namespace App\Models;

use App\Models\Document;
use App\Models\Team;
use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'document_id', 'external_id', 'vendor_name', 'receipt_number', 'issue_date', 'total_amount', 'currency', 'status', 'reference'])]
class Receipt extends Model
{
    use HasFactory, BelongsToTeam;

    public const STATUS_PAID = 'paid';
    public const STATUS_UNPAID = 'unpaid';

    protected $fillable = [
        'team_id',
        'document_id',
        'external_id',
        'vendor_name',
        'receipt_number',
        'issue_date',
        'total_amount',
        'currency',
        'status',
        'reference',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
