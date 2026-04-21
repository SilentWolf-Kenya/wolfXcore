<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotRepo extends Model
{
    protected $table = 'wxn_bot_repos';

    protected $fillable = [
        'name', 'description', 'image_url', 'repo_url',
        'git_address', 'main_file', 'app_json_raw', 'env_schema', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(BotAllocation::class, 'repo_id');
    }

    public function getEnvSchemaArrayAttribute(): array
    {
        return json_decode($this->env_schema ?? '{}', true) ?? [];
    }

    public function getAvailableSlots(): int
    {
        return $this->allocations()->where('status', 'available')->count();
    }

    public function getTotalSlots(): int
    {
        return $this->allocations()->count();
    }
}
