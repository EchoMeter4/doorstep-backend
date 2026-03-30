<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function visitors()
    {
        return $this->hasMany(Visitor::class);
    }
}
