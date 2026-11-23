<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Paddle;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    private User $user;

    private Court $court;

    private string $tomorrow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BookingService::class);
        $this->user = User::factory()->create();
        $this->court = Court::factory()->create(['hourly_rate' => 400]);
        $this->tomorrow = now()->addDay()->toDateString();
    }

    private function book(array $overrides = [], ?User $user = null): Booking
    {
        return $this->service->create($user ?? $this->user, array_merge([
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ], $overrides));
    }

    /** Assert the callback throws a validation error on the given field. */
    private function assertRejected(string $field, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors(), 'Errors were: '.json_encode($e->errors()));

            return;
        }

        $this->fail("Expected a validation error on [{$field}].");
    }

    public function test_booking_a_court_calculates_the_fee(): void
    {
        $booking = $this->book();

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame('08:00:00', $booking->start_time);
        $this->assertEquals(2, $booking->hours);
        $this->assertEquals(800, $booking->court_fee);
        $this->assertEquals(0, $booking->paddle_fee);
        $this->assertEquals(800, $booking->total_amount);
        $this->assertStringStartsWith('PB-', $booking->reference);
    }

    public function test_overlapping_booking_on_same_court_is_rejected(): void
    {
        $this->book();

        $this->assertRejected('start_time', fn () => $this->book(['start_time' => '09:00', 'end_time' => '11:00']));
    }

    public function test_back_to_back_and_other_court_bookings_are_allowed(): void
    {
        $this->book();
        $this->book(['start_time' => '10:00', 'end_time' => '11:00']);
        $this->book(['court_id' => Court::factory()->create()->id]);

        $this->assertDatabaseCount('bookings', 3);
    }

    public function test_cancelled_booking_frees_the_slot(): void
    {
        $booking = $this->book();
        $this->assertSame(BookingStatus::Cancelled, $this->service->cancel($booking)->status);

        $this->book();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_booking_outside_operating_hours_is_rejected(): void
    {
        $this->assertRejected('start_time', fn () => $this->book(['start_time' => '21:00', 'end_time' => '23:00']));
        $this->assertRejected('start_time', fn () => $this->book(['start_time' => '05:00', 'end_time' => '07:00']));
    }

    public function test_booking_in_the_past_is_rejected(): void
    {
        $this->assertRejected('start_time', fn () => $this->book(['booking_date' => now()->subDay()->toDateString()]));
    }

    public function test_booking_length_limits_come_from_config(): void
    {
        config(['booking.max_hours' => 2]);

        $this->assertRejected('end_time', fn () => $this->book(['start_time' => '08:00', 'end_time' => '11:00']));
        $this->assertRejected('end_time', fn () => $this->book(['start_time' => '08:00', 'end_time' => '08:30']));
        $this->assertRejected('end_time', fn () => $this->book(['start_time' => '10:00', 'end_time' => '09:00']));

        $this->book(['start_time' => '08:00', 'end_time' => '10:00']);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_times_must_be_on_allowed_minute_marks(): void
    {
        $this->assertRejected('start_time', fn () => $this->book(['start_time' => '08:15', 'end_time' => '09:15']));

        $this->book(['start_time' => '08:30', 'end_time' => '09:30']);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_cannot_book_beyond_the_booking_window(): void
    {
        config(['booking.max_days_ahead' => 7]);

        $this->assertRejected('booking_date', fn () => $this->book(['booking_date' => now()->addDays(8)->toDateString()]));

        $this->book(['booking_date' => now()->addDays(7)->toDateString()]);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_date_is_always_stored_as_a_plain_date(): void
    {
        // A Carbon value with a time part must still be stored as Y-m-d, or same-day overlap checks would miss it.
        $booking = Booking::factory()->create(['court_id' => $this->court->id, 'booking_date' => now()->addDay()]);

        $this->assertSame($this->tomorrow, $booking->getRawOriginal('booking_date'));
        $this->assertRejected('start_time', fn () => $this->book()); // overlaps 08:00–10:00 from the factory
    }

    public function test_paddle_quantity_limits(): void
    {
        config(['booking.max_paddles_per_type' => 3]);
        $paddle = Paddle::factory()->create(['stock_quantity' => 10]);
        $booking = $this->book();

        $this->assertRejected('paddles', fn () => $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 0]]));
        $this->assertRejected('paddles', fn () => $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 4]]));

        $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 2]]);
        // 2 already + 2 more would exceed the per-booking limit of 3.
        $this->assertRejected('paddles', fn () => $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 2]]));
    }

    public function test_paddle_availability_uses_a_fixed_number_of_queries(): void
    {
        $paddles = Paddle::factory()->count(5)->create(['stock_quantity' => 4]);
        $this->book(['paddles' => [['paddle_id' => $paddles[0]->id, 'quantity' => 3]]]);

        DB::enableQueryLog();
        $result = $this->service->paddleAvailability($this->tomorrow, '09:00', '10:00')->keyBy('id');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(2, $queries);
        $this->assertSame(1, $result[$paddles[0]->id]->available_quantity);
        $this->assertSame(4, $result[$paddles[1]->id]->available_quantity);
    }

    public function test_upcoming_and_past_scopes(): void
    {
        $this->travelTo(today()->setTime(12, 0));

        $endedToday = Booking::factory()->for($this->user)->create(['court_id' => $this->court->id, 'booking_date' => today(), 'start_time' => '08:00:00', 'end_time' => '09:00:00']);
        $laterToday = Booking::factory()->for($this->user)->create(['court_id' => $this->court->id, 'booking_date' => today(), 'start_time' => '15:00:00', 'end_time' => '16:00:00']);
        $tomorrow = Booking::factory()->for($this->user)->create(['court_id' => $this->court->id, 'booking_date' => today()->addDay()]);
        Booking::factory()->for($this->user)->status(BookingStatus::Cancelled)->create(['court_id' => $this->court->id, 'booking_date' => today()->addDay(), 'start_time' => '18:00:00', 'end_time' => '19:00:00']);

        $this->assertEqualsCanonicalizing([$laterToday->id, $tomorrow->id], Booking::upcoming()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$endedToday->id], Booking::past()->pluck('id')->all());
    }

    public function test_inactive_court_cannot_be_booked(): void
    {
        $this->court->update(['is_active' => false]);

        $this->assertRejected('court_id', fn () => $this->book());
    }

    public function test_paddles_can_be_rented_with_the_booking(): void
    {
        $paddle = Paddle::factory()->create(['hourly_rate' => 50, 'stock_quantity' => 4]);

        $booking = $this->book(['paddles' => [['paddle_id' => $paddle->id, 'quantity' => 2]]]);

        $this->assertEquals(200, $booking->paddle_fee); // 50 x 2 hours x 2 paddles
        $this->assertEquals(1000, $booking->total_amount);
        $this->assertSame(2, $booking->rentals->first()->quantity);
    }

    public function test_paddles_can_be_added_after_booking(): void
    {
        $paddle = Paddle::factory()->create(['hourly_rate' => 50, 'stock_quantity' => 4]);
        $booking = $this->book();

        $booking = $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 1]]);
        $this->assertEquals(100, $booking->paddle_fee);

        // Adding the same paddle again increases its quantity.
        $booking = $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 2]]);
        $this->assertSame(3, $booking->rentals->first()->quantity);
        $this->assertEquals(300, $booking->paddle_fee);
        $this->assertEquals(1100, $booking->total_amount);
        $this->assertDatabaseCount('booking_paddle', 1);
    }

    public function test_cannot_rent_more_paddles_than_free_for_the_slot(): void
    {
        $paddle = Paddle::factory()->create(['stock_quantity' => 3]);

        // Someone on another court rents 2 at an overlapping time.
        $this->book([
            'court_id' => Court::factory()->create()->id,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'paddles' => [['paddle_id' => $paddle->id, 'quantity' => 2]],
        ], User::factory()->create());

        $booking = $this->book();

        $this->assertRejected('paddles', fn () => $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 2]]));

        $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 1]]);
        $this->assertSame(0, $paddle->availableQuantityDuring($this->tomorrow, '09:00:00', '10:00:00'));
    }

    public function test_paddles_are_free_again_for_non_overlapping_slots_and_after_cancel(): void
    {
        $paddle = Paddle::factory()->create(['stock_quantity' => 1]);

        $first = $this->book(['paddles' => [['paddle_id' => $paddle->id, 'quantity' => 1]]]);

        // Later slot on another court can use the same paddle.
        $this->book([
            'court_id' => Court::factory()->create()->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'paddles' => [['paddle_id' => $paddle->id, 'quantity' => 1]],
        ]);

        $this->assertSame(0, $paddle->availableQuantityDuring($this->tomorrow, '08:00:00', '10:00:00'));
        $this->service->cancel($first);
        $this->assertSame(1, $paddle->availableQuantityDuring($this->tomorrow, '08:00:00', '10:00:00'));
    }

    public function test_paddle_rental_can_be_removed(): void
    {
        $paddle = Paddle::factory()->create();
        $booking = $this->book(['paddles' => [['paddle_id' => $paddle->id, 'quantity' => 1]]]);

        $booking = $this->service->removePaddle($booking, $paddle);

        $this->assertEquals(0, $booking->paddle_fee);
        $this->assertEquals(800, $booking->total_amount);
        $this->assertCount(0, $booking->rentals);
    }

    public function test_cancelled_or_completed_booking_cannot_be_changed(): void
    {
        $paddle = Paddle::factory()->create();
        $booking = $this->service->cancel($this->book());

        $this->assertRejected('booking', fn () => $this->service->addPaddles($booking, [['paddle_id' => $paddle->id, 'quantity' => 1]]));

        $completed = Booking::factory()->for($this->user)->status(BookingStatus::Completed)->create(['court_id' => $this->court->id, 'start_time' => '12:00:00', 'end_time' => '13:00:00']);
        $this->assertRejected('booking', fn () => $this->service->cancel($completed));
    }

    public function test_admin_status_transitions(): void
    {
        $booking = $this->book();

        $booking = $this->service->changeStatus($booking, BookingStatus::Confirmed);
        $booking = $this->service->changeStatus($booking, BookingStatus::Completed);
        $this->assertSame(BookingStatus::Completed, $booking->status);

        $this->assertRejected('status', fn () => $this->service->changeStatus($booking, BookingStatus::Cancelled));
    }

    public function test_court_slots_mark_booked_hours(): void
    {
        $this->book();

        $slots = collect($this->service->courtSlots($this->court, $this->tomorrow))->keyBy('start_time');

        $this->assertCount(16, $slots); // 06:00 - 22:00
        $this->assertFalse($slots['08:00']['available']);
        $this->assertFalse($slots['09:00']['available']);
        $this->assertTrue($slots['10:00']['available']);
    }

    public function test_booking_policy_only_allows_owner_or_admin(): void
    {
        $booking = $this->book();

        $this->assertTrue($this->user->can('view', $booking));
        $this->assertFalse(User::factory()->create()->can('view', $booking));
        $this->assertTrue(User::factory()->admin()->create()->can('update', $booking));
    }
}
