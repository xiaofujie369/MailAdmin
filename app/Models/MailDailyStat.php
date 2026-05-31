<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailDailyStat extends Model
{
    protected $fillable = [
        'server_id', 'date', 'sent_count', 'bounced_count', 'deferred_count', 'reject_count',
        'warning_count', 'connect_count', 'auth_count', 'spam_count',
        'failure_rate', 'bounce_rate', 'deferred_rate',
    ];
}
