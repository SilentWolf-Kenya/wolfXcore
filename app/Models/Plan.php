<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'memory',
        'cpu',
        'disk',
        'io',
        'databases',
        'backups',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
    ];

    /**
     * Format memory in a human-readable way.
     */
    public function getMemoryFormattedAttribute(): string
    {
        if ($this->memory == 0) return 'Unlimited';
        if ($this->memory >= 1024) return round($this->memory / 1024, 1) . ' GB';
        return $this->memory . ' MB';
    }

    public function getDiskFormattedAttribute(): string
    {
        if ($this->disk == 0) return 'Unlimited';
        if ($this->disk >= 1024) return round($this->disk / 1024, 1) . ' GB';
        return $this->disk . ' MB';
    }

    public function getCpuFormattedAttribute(): string
    {
        if ($this->cpu == 0) return 'Unlimited';
        return $this->cpu . '%';
    }
}
