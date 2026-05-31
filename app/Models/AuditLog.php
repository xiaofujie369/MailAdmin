<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'action', 'target_type', 'target_id', 'ip', 'user_agent', 'payload', 'created_at'];
    protected $casts = ['payload' => 'array', 'created_at' => 'datetime'];
}
