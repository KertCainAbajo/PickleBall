<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'brand', 'description', 'hourly_rate', 'stock_quantity', 'is_active'])]
class Paddle extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class)
            ->withPivot(['id', 'quantity', 'unit_price', 'subtotal'])
            ->withTimestamps();
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
     * Units of this paddle already rented by active bookings that overlap the given slot.
     */
    public function rentedQuantityDuring(string $date, string $start, string $end, ?int $ignoreBookingId = null): int
    {
        return (int) BookingPaddle::query()
            ->where('paddle_id', $this->id)
            ->whereHas('booking', function (Builder $q) use ($date, $start, $end, $ignoreBookingId) {
                $q->whereDate('booking_date', $date)
                    ->whereIn('status', BookingStatus::active())
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->when($ignoreBookingId, fn (Builder $q) => $q->whereKeyNot($ignoreBookingId));
            })
            ->sum('quantity');
    }

    public function availableQuantityDuring(string $date, string $start, string $end, ?int $ignoreBookingId = null): int
    {
        return max(0, $this->stock_quantity - $this->rentedQuantityDuring($date, $start, $end, $ignoreBookingId));
    }
}
