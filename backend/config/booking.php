<?php

/*
|--------------------------------------------------------------------------
| Booking rules
|--------------------------------------------------------------------------
|
| Enforced by App\Services\BookingService, so every screen (and any future
| API or admin tool) follows the same rules.
|
*/

return [

    // How many days ahead a court can be booked (0 = today only).
    'max_days_ahead' => (int) env('BOOKING_MAX_DAYS_AHEAD', 30),

    // Shortest and longest single booking, in hours.
    'min_hours' => 1,
    'max_hours' => (int) env('BOOKING_MAX_HOURS', 4),

    // Bookings must start/end on these minute marks.
    'allowed_minutes' => [0, 30],

    // Most units of one paddle type a single booking can rent.
    'max_paddles_per_type' => 10,

];
