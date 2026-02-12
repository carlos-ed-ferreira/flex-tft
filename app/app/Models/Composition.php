<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Composition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'notes',
    ];

    public function levels(): HasMany
    {
        return $this->hasMany(CompositionLevel::class)->orderBy('level');
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(CompositionDisposition::class)->orderBy('priority');
    }

    /**
     * Get the board state for a specific level.
     */
    public function getBoardForLevel(int $level): array
    {
        $compositionLevel = $this->levels()->where('level', $level)->first();
        return $compositionLevel ? $compositionLevel->board_state : [];
    }
}
