<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkEntry extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'cable_type',
        'work_types',
        'enclosure_id',
        'street_id',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'work_types' => 'array',
    ];

    const CABLE_TYPES = [
        '1R_71', '1R_73', '1R_85', '1R_87',
        '2R_71', '2R_73', '2R_85', '2R_87',
        '4R_71', '4R_73', '4R_85', '4R_87',
        '6R_71', '6R_73', '12R_71', '12R_73',
    ];

    const WORK_TYPES = [
        'uvlačenje' => 'Uvlačenje',
        'iskop' => 'Iskop',
        'otvaranje_rupa' => 'Otvaranje rupa',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function enclosure(): BelongsTo
    {
        return $this->belongsTo(Enclosure::class);
    }

    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }
}
