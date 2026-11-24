<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Livewire\Admin\Bookings as AdminBookings;
use App\Livewire\Admin\Courts as AdminCourts;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Paddles as AdminPaddles;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\BookCourt;
use App\Livewire\Bookings\Show;
use App\Livewire\RentPaddles;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Paddle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_load(): void
    {
        Court::factory()->create(['name' => 'Court A']);

        $this->get('/')->assertOk()->assertSee('Court A');
        $this->get('/book')->assertOk()->assertSee('Court A');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guests_are_sent_to_login_for_private_pages(): void
    {
        $this->get('/bookings')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_customer_cannot_open_admin_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_pages_load(): void
    {
        $admin = User::factory()->admin()->create();
        Booking::factory()->create(['booking_date' => today()->toDateString(), 'start_time' => '21:00:00', 'end_time' => '22:00:00']);

        foreach (['/admin', '/admin/bookings', '/admin/courts', '/admin/paddles'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_register_and_login(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Maria Santos')
            ->set('email', 'maria@example.com')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('book'));

        $this->assertAuthenticated();
        $this->assertSame('customer', User::first()->role->value);

        // Log out through the real logout route.
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        Livewire::test(Login::class)
            ->set('email', 'maria@example.com')
            ->set('password', 'wrong')
            ->call('login')
            ->assertHasErrors('email');

        Livewire::test(Login::class)
            ->set('email', 'maria@example.com')
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect(route('book'));
    }

    public function test_full_booking_flow_with_paddle_rental(): void
    {
        $user = User::factory()->create();
        $court = Court::factory()->create(['hourly_rate' => 500]);
        $paddle = Paddle::factory()->create(['hourly_rate' => 50, 'stock_quantity' => 3]);
        $tomorrow = now()->addDay()->toDateString();

        $this->actingAs($user);

        // Step 1: pick court, date, 8 AM, extend to 2 hours, book.
        Livewire::test(BookCourt::class)
            ->call('selectCourt', $court->id)
            ->call('selectDate', $tomorrow)
            ->call('selectSlot', '08:00')
            ->call('selectSlot', '09:00') // next slot extends the selection
            ->assertSet('duration', 2)
            ->assertViewHas('total', 1000.0)
            ->assertViewHas('selectedStarts', ['08:00', '09:00'])
            ->call('book')
            ->assertHasNoErrors();

        $booking = Booking::sole();
        $this->assertSame('08:00:00', $booking->start_time);
        $this->assertSame('10:00:00', $booking->end_time);

        // Step 2: rent 2 paddles.
        Livewire::test(RentPaddles::class, ['booking' => $booking])
            ->call('increment', $paddle->id)
            ->call('increment', $paddle->id)
            ->assertSet('paddleTotal', 200.0)
            ->call('save')
            ->assertRedirect(route('bookings.show', $booking));

        $this->assertEquals(1200, $booking->fresh()->total_amount);

        // Booking details: remove the paddles, then cancel.
        Livewire::test(Show::class, ['booking' => $booking])
            ->assertSee($booking->reference)
            ->call('removePaddle', $paddle->id)
            ->call('cancel');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertEquals(1000, $booking->fresh()->total_amount);
    }

    public function test_booking_page_renders_the_time_buttons(): void
    {
        $court = Court::factory()->create();
        $tomorrow = now()->addDay()->toDateString();

        // Regression: a view variable named $slots is overwritten by Livewire 4, which left the grid empty.
        $this->get(route('book', ['court' => $court->id, 'date' => $tomorrow]))
            ->assertOk()
            ->assertSee('wire:key="slot-06:00"', false)
            ->assertSee('wire:key="slot-21:00"', false);
    }

    public function test_schedule_link_preselects_a_free_hour_but_not_a_taken_one(): void
    {
        $court = Court::factory()->create();
        $tomorrow = now()->addDay()->toDateString();
        Booking::factory()->create(['court_id' => $court->id, 'booking_date' => $tomorrow, 'start_time' => '10:00:00', 'end_time' => '11:00:00']);

        Livewire::withQueryParams(['court' => $court->id, 'date' => $tomorrow, 'start' => '08:00'])
            ->test(BookCourt::class)
            ->assertSet('startTime', '08:00')
            ->assertViewHas('total', 400.0);

        Livewire::withQueryParams(['court' => $court->id, 'date' => $tomorrow, 'start' => '10:00'])
            ->test(BookCourt::class)
            ->assertSet('startTime', null);
    }

    public function test_home_schedule_links_free_hours_to_booking(): void
    {
        $this->travelTo(today()->setTime(9, 30));
        $court = Court::factory()->create();

        $this->get('/')
            ->assertOk()
            // escaped, because "&" between query params is written as &amp; in HTML
            ->assertSee(route('book', ['court' => $court->id, 'date' => today()->toDateString(), 'start' => '10:00']));
    }

    public function test_taken_slots_cannot_be_selected(): void
    {
        $court = Court::factory()->create();
        $tomorrow = now()->addDay()->toDateString();
        Booking::factory()->create(['court_id' => $court->id, 'booking_date' => $tomorrow, 'start_time' => '08:00:00', 'end_time' => '09:00:00']);

        $this->actingAs(User::factory()->create());

        Livewire::test(BookCourt::class)
            ->call('selectCourt', $court->id)
            ->call('selectDate', $tomorrow)
            ->call('selectSlot', '08:00')
            ->assertSet('startTime', null)
            ->call('selectSlot', '07:00')
            ->call('selectSlot', '08:00') // can't extend into the taken hour
            ->assertSet('duration', 1)
            ->assertViewHas('maxDuration', 1);
    }

    public function test_guest_clicking_book_is_sent_to_login(): void
    {
        $court = Court::factory()->create();

        Livewire::test(BookCourt::class)
            ->call('selectCourt', $court->id)
            ->call('selectDate', now()->addDay()->toDateString())
            ->call('selectSlot', '08:00')
            ->call('book')
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_customer_cannot_open_someone_elses_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('bookings.show', $booking))
            ->assertForbidden();
    }

    public function test_admin_can_confirm_from_the_dashboard_queue(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = Booking::factory()->create();

        Livewire::test(Dashboard::class)
            ->assertSee($booking->user->name)
            ->call('changeStatus', $booking->id, 'confirmed')
            ->assertSee('is now confirmed')
            ->assertSee('All caught up');

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_admin_can_upload_replace_and_remove_a_paddle_photo(): void
    {
        $disk = Storage::fake('uploads');
        $this->actingAs(User::factory()->admin()->create());
        $paddle = Paddle::factory()->create();

        // Upload
        Livewire::test(AdminPaddles::class)
            ->call('edit', $paddle->id)
            ->set('photo', UploadedFile::fake()->image('paddle.jpg', 800, 600))
            ->call('save')
            ->assertHasNoErrors();

        $first = $paddle->fresh()->image_path;
        $this->assertStringStartsWith('uploads/paddles/', $first);
        $disk->assertExists(substr($first, strlen('uploads/')));

        // Replace: the old file is deleted
        Livewire::test(AdminPaddles::class)
            ->call('edit', $paddle->id)
            ->set('photo', UploadedFile::fake()->image('new.jpg', 800, 600))
            ->call('save');

        $second = $paddle->fresh()->image_path;
        $this->assertNotSame($first, $second);
        $disk->assertMissing(substr($first, strlen('uploads/')));

        // Remove
        Livewire::test(AdminPaddles::class)
            ->call('edit', $paddle->id)
            ->set('removePhoto', true)
            ->call('save');

        $this->assertNull($paddle->fresh()->image_path);
        $disk->assertMissing(substr($second, strlen('uploads/')));

        // Non-images are rejected
        Livewire::test(AdminPaddles::class)
            ->call('edit', $paddle->id)
            ->set('photo', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
            ->assertHasErrors('photo');
    }

    public function test_admin_can_confirm_booking_and_manage_courts_and_paddles(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $booking = Booking::factory()->create();

        Livewire::test(AdminBookings::class)
            ->call('changeStatus', $booking->id, 'confirmed')
            ->assertSee('is now confirmed');
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        Livewire::test(AdminCourts::class)
            ->call('create')
            ->set('name', 'Court 9')
            ->set('hourly_rate', '450')
            ->set('opening_time', '07:00')
            ->set('closing_time', '21:00')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('courts', ['name' => 'Court 9', 'hourly_rate' => 450]);

        Livewire::test(AdminPaddles::class)
            ->call('create')
            ->set('name', 'Pro Paddle')
            ->set('hourly_rate', '120')
            ->set('stock_quantity', '8')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('paddles', ['name' => 'Pro Paddle', 'stock_quantity' => 8]);
    }
}
