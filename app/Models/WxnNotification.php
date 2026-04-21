<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class WxnNotification extends Model
{
    protected $table = 'wxn_notifications';

    protected $fillable = ['title', 'body', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
