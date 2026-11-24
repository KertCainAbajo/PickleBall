<?php

namespace App\Models;

use App\Models\Concerns\HasPhoto;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string|null $brand
 * @property string|null $description
 * @property string $hourly_rate
 * @property int $stock_quantity
 * @property bool $is_active
 * @property string|null $image_path relative to public/, e.g. "uploads/paddles/abc.jpg"
 * @property-read string|null $image_url
 * @property int $available_quantity only set by BookingService::paddleAvailability()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingPaddle> $rentals
 */
#[Fillable(['name', 'brand', 'description', 'image_path', 'hourly_rate', 'stock_quantity', 'is_active'])]
class Paddle extends Model
{
    use HasFactory, HasPhoto;

    /** Folder (inside the "uploads" disk) where admin-uploaded paddle photos go. */
    public const UPLOAD_DIR = 'paddles';

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(BookingPaddle::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Units already rented per paddle by active bookings overlapping a slot — one query for all paddles.
     *
     * @param  array<int>|null  $paddleIds  limit to these paddles (null = all)
     * @return Collection<int, int> paddle_id => units rented
     */
    public static function rentedQuantities(Carbon|string $date, string $start, string $end, ?array $paddleIds = null): Collection
    {
        return BookingPaddle::query()
            ->whereIn('booking_id', Booking::query()->select('id')->overlappingTime($date, $start, $end))
            ->when($paddleIds !== null, fn (Builder $q) => $q->whereIn('paddle_id', $paddleIds))
            ->groupBy('paddle_id')
            ->selectRaw('paddle_id, SUM(quantity) as rented')
            ->pluck('rented', 'paddle_id')
            ->map(fn ($rented) => (int) $rented);
    }

    /** Units of this paddle still free for the given slot. */
    public function availableQuantityDuring(Carbon|string $date, string $start, string $end): int
    {
        $rented = static::rentedQuantities($date, $start, $end, [$this->id])->get($this->id, 0);

        return max(0, $this->stock_quantity - $rented);
    }
}
