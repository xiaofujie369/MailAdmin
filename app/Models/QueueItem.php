<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueItem extends Model
{
    protected $fillable = [
        'server_id', 'queue_id', 'sender', 'recipient', 'recipient_domain',
        'size', 'queued_at', 'reason', 'raw_block',
    ];
}
