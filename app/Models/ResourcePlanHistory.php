<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePlanHistory extends Model
{
    public $timestamps = false;

    protected $table = 'resource_plan_history';

    protected $fillable = ['plan_id', 'user_id', 'action', 'data', 'created_at'];

    protected $casts = [
        'data'       => 'array',
        'created_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ResourcePlan::class, 'plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
