<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Composition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'notes',
        'is_global',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(CompositionLevel::class)->orderBy('level');
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(CompositionDisposition::class)->orderBy('priority');
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('is_global', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
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
