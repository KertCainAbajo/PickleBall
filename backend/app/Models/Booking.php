<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference
 * @property int $user_id
 * @property int $court_id
 * @property Carbon $booking_date
 * @property string $start_time "HH:MM:SS"
 * @property string $end_time "HH:MM:SS"
 * @property string $hours
 * @property string $court_fee
 * @property string $paddle_fee
 * @property string $total_amount
 * @property BookingStatus $status
 * @property string|null $notes
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Court $court
 * @property-read Collection<int, BookingPaddle> $rentals
 *
 * @method static Builder<static> active()
 * @method static Builder<static> onDate(Carbon|string $date)
 * @method static Builder<static> overlappingTime(Carbon|string $date, string $start, string $end)
 * @method static Builder<static> overlapping(int $courtId, Carbon|string $date, string $start, string $end)
 * @method static Builder<static> upcoming()
 * @method static Builder<static> past()
 */
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
            'booking_date' => DateOnly::class,
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

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Bookings that hold their court/paddles (pending or confirmed). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::active());
    }

    public function scopeOnDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->where('booking_date', Carbon::parse($date)->toDateString());
    }

    /** Active bookings whose time range overlaps [start, end) on the given date. */
    public function scopeOverlappingTime(Builder $query, Carbon|string $date, string $start, string $end): Builder
    {
        return $query->onDate($date)
            ->active()
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    /** Active bookings on a court that overlap the given slot. */
    public function scopeOverlapping(Builder $query, int $courtId, Carbon|string $date, string $start, string $end): Builder
    {
        return $query->where('court_id', $courtId)->overlappingTime($date, $start, $end);
    }

    /** Active bookings that have not ended yet. */
    public function scopeUpcoming(Builder $query): Builder
    {
        $today = today()->toDateString();

        return $query->active()->where(fn (Builder $q) => $q
            ->where('booking_date', '>', $today)
            ->orWhere(fn (Builder $q) => $q->where('booking_date', $today)->where('end_time', '>', now()->format('H:i:s'))));
    }

    /** Non-cancelled bookings that are completed or have already ended. */
    public function scopePast(Builder $query): Builder
    {
        $today = today()->toDateString();

        return $query->where('status', '!=', BookingStatus::Cancelled->value)->where(fn (Builder $q) => $q
            ->where('status', BookingStatus::Completed->value)
            ->orWhere('booking_date', '<', $today)
            ->orWhere(fn (Builder $q) => $q->where('booking_date', $today)->where('end_time', '<=', now()->format('H:i:s'))));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** Whether the booking can still be changed (cancelled, paddles added/removed). */
    public function isModifiable(): bool
    {
        return $this->status->isActive() && ! $this->startsAt()->isPast();
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString().' '.$this->start_time);
    }

    public function endsAt(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString().' '.$this->end_time);
    }

    /** Recompute paddle_fee and total_amount from the rental lines and save. */
    public function recalculateTotals(): void
    {
        $paddleFee = (float) $this->rentals()->sum('subtotal');

        $this->paddle_fee = number_format($paddleFee, 2, '.', '');
        $this->total_amount = number_format((float) $this->court_fee + $paddleFee, 2, '.', '');
        $this->save();
    }
}
