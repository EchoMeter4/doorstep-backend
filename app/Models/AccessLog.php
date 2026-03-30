<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'log_code',
        'user_id',
        'zone_id',
        'action_type',
        'is_authorized',
        'access_type',
        'credential_type',
        'credential_value',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_authorized' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'log_user', 'access_log_id', 'user_id');
    }
}
