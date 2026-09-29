<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avanto extends Model
{
    use HasFactory;

    protected $table = 'new_avanto';

    protected $primaryKey = 'avanto_id';

    protected $fillable = [
        'date',
        'location',
        'water_temperature',
        'duration_minutes',
        'duration_seconds',
        'swear_words',
        'feeling_before',
        'feeling_after',
        'selfie_path',
        'sauna',
        'sauna_duration',
    ];

    protected $casts = [
        'date' => 'date',
        'water_temperature' => 'float',
        'sauna' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalDurationAttribute(): int
    {
        return ($this->duration_minutes * 60) + ($this->duration_seconds ?? 0);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field ??= $this->getRouteKeyName();

        return $this->where($field, $value)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }
}
