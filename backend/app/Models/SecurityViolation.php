<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'violation_type',
        'ip_address',
        'device_info',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
