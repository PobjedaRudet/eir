<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectService extends Model
{
    protected $table = 'project_services';

    protected $fillable = ['name', 'category', 'unit', 'description'];

    public const CATEGORIES = [
        'transport'  => 'Transport',
        'najam'      => 'Najam opreme',
        'odrzavanje' => 'Održavanje',
        'ostalo'     => 'Ostalo',
    ];

    public const UNITS = ['sat', 'dan', 'tjedan', 'kom', 'km'];
}
