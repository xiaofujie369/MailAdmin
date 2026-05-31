<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_id', 'event_time', 'queue_id', 'event_type', 'status', 'sender', 'recipient',
        'recipient_domain', 'client_ip', 'relay', 'dsn', 'delay', 'message', 'raw_line', 'raw_hash', 'created_at',
    ];

    protected $casts = ['event_time' => 'datetime', 'created_at' => 'datetime'];
}
