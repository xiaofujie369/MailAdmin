<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailServer extends Model
{
    protected $fillable = ['name', 'hostname', 'container_name', 'domain', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
