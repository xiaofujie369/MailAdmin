<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DnsCheck extends Model
{
    protected $fillable = [
        'domain', 'mx_result', 'spf_result', 'dkim_mail_result', 'dkim_default_result',
        'dmarc_result', 'a_result', 'ptr_result', 'score', 'checked_at',
    ];

    protected $casts = ['checked_at' => 'datetime'];
}
