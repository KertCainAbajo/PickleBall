<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A paddle rental line attached to a booking.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $paddle_id
 * @property int $quantity
 * @property string $unit_price hourly rate at the time of rental
 * @property string $subtotal
 * @property-read Booking $booking
 * @property-read Paddle $paddle
 */
#[Fillable(['booking_id', 'paddle_id', 'quantity', 'unit_price', 'subtotal'])]
class BookingPaddle extends Model
{
    protected $table = 'booking_paddle';

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function paddle(): BelongsTo
    {
        return $this->belongsTo(Paddle::class);
    }
}
