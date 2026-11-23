<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2);
            $table->decimal('court_fee', 10, 2);
            $table->decimal('paddle_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('status', 20)->default('pending'); // pending | confirmed | completed | cancelled
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['court_id', 'booking_date']);
            $table->index(['booking_date', 'status']);
        });

        Schema::create('booking_paddle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paddle_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2); // hourly rate at the time of rental
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->unique(['booking_id', 'paddle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_paddle');
        Schema::dropIfExists('bookings');
    }
};
