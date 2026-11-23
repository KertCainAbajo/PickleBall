<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Statuses that hold the court and paddles for their time slot.
     *
     * @return array<int, string>
     */
    public static function active(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }

    public function isActive(): bool
    {
        return in_array($this->value, self::active(), true);
    }
}
