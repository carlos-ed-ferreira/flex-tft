<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositionLevel extends Model
{
    protected $fillable = [
        'composition_id',
        'level',
        'board_state',
    ];

    protected $casts = [
        'board_state' => 'array',
        'level' => 'integer',
    ];

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Composition::class);
    }

    /**
     * Get the number of champions placed on this level's board.
     */
    public function getChampionCountAttribute(): int
    {
        return count(array_filter($this->board_state ?? [], fn($cell) => !empty($cell['championId'])));
    }
}
