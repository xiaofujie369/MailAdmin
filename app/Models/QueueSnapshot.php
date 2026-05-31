<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueSnapshot extends Model
{
    public $timestamps = false;
    protected $fillable = ['server_id', 'captured_at', 'queue_count', 'raw_output', 'created_at'];
    protected $casts = ['captured_at' => 'datetime'];
}
