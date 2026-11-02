<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaultLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'user_id', 'error_code', 'severity', 'message', 'stack_trace',
        'file_name', 'file_path', 'line_number', 'class_name', 'method_name',
        'root_cause', 'recommended_fix', 'status',
    ];

    protected $casts = [
        'line_number' => 'integer',
    ];
}
