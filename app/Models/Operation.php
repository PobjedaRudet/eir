<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operation extends Model
{
    protected $fillable = [
        'work_entry_id',
        'kind',
        'street_ids',
        'excavation_type',
        'dimensions',
        'meterage',
        'sub_operations',
        'address',
        'splajsovano',
        'aktivirano',
    ];

    protected $casts = [
        'street_ids'      => 'array',
        'sub_operations' => 'array',
        'splajsovano'    => 'boolean',
        'aktivirano'     => 'boolean',
    ];

    const KINDS = [
        'iskop'       => 'Iskop',
        'upuhivanje'  => 'Upuhivanje kabla',
    ];

    const EXCAVATION_TYPES = [
        'iskop'         => 'Iskop',
        'iskop_flaster' => 'Iskop flaster',
        'iskop_asfalt'  => 'Iskop asfalt',
        'raketa'        => 'Raketa',
    ];

    const DIMENSIONS = ['15x45', '15x60', '30x45', '30x60'];

    const SUB_OPERATION_TYPES = ['HP+'];

    public function workEntry(): BelongsTo
    {
        return $this->belongsTo(WorkEntry::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(OperationImage::class);
    }
}
