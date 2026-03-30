<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plate_number',
        'make',
        'model',
        'year',
        'color',
        'type',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_vehicle');
    }
}
