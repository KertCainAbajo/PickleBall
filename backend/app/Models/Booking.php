<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'user_id', 'court_id', 'booking_date', 'start_time', 'end_time',
    'hours', 'court_fee', 'paddle_fee', 'total_amount', 'status', 'notes', 'cancelled_at',
])]
class Booking extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
        'paddle_fee' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->reference ??= static::generateReference();
        });
    }

    protected function casts(): array
    {
        return [
            'booking_date' => 'date:Y-m-d',
            'hours' => 'decimal:2',
            'court_fee' => 'decimal:2',
            'paddle_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => BookingStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'PB-'.strtoupper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /** Paddle rental lines for this booking. */
    public function rentals(): HasMany
    {
        return $this->hasMany(BookingPaddle::class);
    }

    public function paddles(): BelongsToMany
    {
        return $this->belongsToMany(Paddle::class)
            ->withPivot(['id', 'quantity', 'unit_price', 'subtotal'])
            ->withTimestamps();
    }

    /** Whether the booking can still be changed (cancelled, paddles added/removed). */
    public function isModifiable(): bool
    {
        return $this->status->isActive() && ! $this->startsAt()->isPast();
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString().' '.$this->start_time);
    }

    /**
     * Active bookings on a court whose time range overlaps the given slot.
     */
    public function scopeOverlapping(Builder $query, int $courtId, string $date, string $start, string $end): Builder
    {
        return $query->where('court_id', $courtId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', BookingStatus::active())
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    /**
     * Recompute paddle_fee and total_amount from the rental lines and save.
     */
    public function recalculateTotals(): void
    {
        $paddleFee = (float) BookingPaddle::where('booking_id', $this->id)->sum('subtotal');

        $this->paddle_fee = round($paddleFee, 2);
        $this->total_amount = round((float) $this->court_fee + $paddleFee, 2);
        $this->save();
    }
}
