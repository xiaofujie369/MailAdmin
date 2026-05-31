<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheck extends Model
{
    public $timestamps = false;
    protected $fillable = ['server_id', 'check_type', 'status', 'score', 'message', 'raw_output', 'checked_at', 'created_at'];
    protected $casts = ['checked_at' => 'datetime', 'created_at' => 'datetime'];
}
