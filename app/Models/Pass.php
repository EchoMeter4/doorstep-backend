<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pass extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'visitor_id',
        'created_by',
        'valid_from',
        'valid_until',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function zones()
    {
        return $this->belongsToMany(Zone::class, 'pass_zone');
    }
}
