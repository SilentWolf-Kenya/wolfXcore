<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotAllocation extends Model
{
    protected $table = 'wxn_bot_allocations';

    protected $fillable = [
        'repo_id', 'server_id', 'server_uuid',
        'status', 'user_id', 'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function repo(): BelongsTo
    {
        return $this->belongsTo(BotRepo::class, 'repo_id');
    }
}
