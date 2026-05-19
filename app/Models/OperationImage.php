<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationImage extends Model
{
    protected $fillable = ['operation_id', 'path', 'original_name'];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }
}
