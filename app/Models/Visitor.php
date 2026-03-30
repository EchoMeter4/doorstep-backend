<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'middle_name',
        'first_last_name',
        'second_last_name',
        'email',
        'phone',
        'company',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function passes()
    {
        return $this->hasMany(Pass::class);
    }
}
