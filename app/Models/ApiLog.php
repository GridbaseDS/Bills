<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'api_key_id',
        'method',
        'path',
        'ip_address',
        'request_body',
        'response_status',
        'response_body',
        'duration_ms',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'response_status' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship to the API Key used.
     */
    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }
}
