<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositionLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'composition_id',
        'level',
        'version',
        'label',
        'board_state',
    ];

    protected $casts = [
        'board_state' => 'array',
        'level' => 'integer',
        'version' => 'integer',
    ];

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Composition::class);
    }

    public function getChampionCountAttribute(): int
    {
        return count(array_filter($this->board_state ?? [], fn ($cell) => ! empty($cell['championId'])));
    }
}
