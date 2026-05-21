<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['name', 'category', 'unit', 'description'];

    public const CATEGORIES = [
        'beton' => 'Beton / Asfalt',
        'kabel' => 'Kabel',
        'cijev' => 'Cijevi',
        'pesak' => 'Pijesak / Šljunak',
        'ostalo' => 'Ostalo',
    ];

    public const UNITS = ['kom', 'm', 'm2', 'm3', 'kg', 't', 'l'];
}
