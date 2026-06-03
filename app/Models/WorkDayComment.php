<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkDayComment extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'comment',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
