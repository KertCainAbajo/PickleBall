<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $surface_type
 * @property string $hourly_rate
 * @property string $opening_time "HH:MM:SS"
 * @property string $closing_time "HH:MM:SS"
 * @property bool $is_active
 * @property-read Collection<int, Booking> $bookings
 */
#[Fillable(['name', 'description', 'surface_type', 'hourly_rate', 'opening_time', 'closing_time', 'is_active'])]
class Court extends Model
{
    use HasFactory;

    /** Cache key for the club opening hours shown in the site footer. */
    public const HOURS_CACHE_KEY = 'club-hours';

    protected static function booted(): void
    {
        // Any change to a court can change the club's hours.
        static::saved(fn () => cache()->forget(self::HOURS_CACHE_KEY));
        static::deleted(fn () => cache()->forget(self::HOURS_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
