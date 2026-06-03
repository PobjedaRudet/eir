<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    protected $fillable = ['name', 'date', 'city_id', 'user_id', 'status', 'rejection_note', 'cable_type'];

    const STATUS_NA_CEKANJU = 'na_cekanju';
    const STATUS_AKTIVAN    = 'aktivan';
    const STATUS_ZAKLJUCEN  = 'zakljucen';
    const STATUS_ODBIJEN    = 'odbijen';

    const CABLE_8Y0001_1 = '8Y0001_1';
    const CABLE_8Y0001_2 = '8Y0001_2';
    const CABLE_8Y0001_3 = '8Y0001_3';

    const CABLE_TYPES = [
        self::CABLE_8Y0001_1 => '8Y0001_1',
        self::CABLE_8Y0001_2 => '8Y0001_2',
        self::CABLE_8Y0001_3 => '8Y0001_3',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function streets(): BelongsToMany
    {
        return $this->belongsToMany(Street::class);
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_worker')
            ->withPivot(['cable_types', 'enclosure_ids']);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'project_equipment')
            ->withPivot('assigned_date', 'status', 'quantity');
    }

    public function workEntries(): HasMany
    {
        return $this->hasMany(WorkEntry::class);
    }

    public function resourcePlans(): HasMany
    {
        return $this->hasMany(ResourcePlan::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ProjectTeam::class);
    }

    public function gradiliste(): HasOne
    {
        return $this->hasOne(Gradiliste::class);
    }

    public function projectNtvs(): HasMany
    {
        return $this->hasMany(ProjectNtv::class);
    }
}
