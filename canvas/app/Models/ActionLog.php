<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionLog extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action_type',
        'target_resource',
        'target_id',
        'payload',
        'status',
        'response_message',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Log thuộc về user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
