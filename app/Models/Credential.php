<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Credential extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'credential_code',
        'is_active',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'issued_at' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
