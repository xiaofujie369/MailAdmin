<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailMonthlyStat extends Model
{
    protected $fillable = [
        'server_id', 'month', 'sent_count', 'bounced_count', 'deferred_count',
        'reject_count', 'failure_rate', 'bounce_rate', 'deferred_rate',
    ];
}
