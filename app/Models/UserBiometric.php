<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBiometric extends Model
{
    use HasFactory;

    protected $table = 'user_biometrics';

    protected $fillable = [
        'user_id',
        'device_token',
        'credential_id',
        'public_key',
        'sign_count',
        'authenticator_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(UserDevice::class, 'device_token', 'device_token');
    }
}
