<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositionDisposition extends Model
{
    protected $fillable = [
        'composition_id',
        'type',
        'champion_ids',
        'star_level',
        'trait_id',
        'trait_count',
        'item_ids',
        'priority',
    ];

    protected $casts = [
        'champion_ids' => 'array',
        'item_ids' => 'array',
        'star_level' => 'integer',
        'trait_count' => 'integer',
        'priority' => 'integer',
    ];

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Composition::class);
    }
}
