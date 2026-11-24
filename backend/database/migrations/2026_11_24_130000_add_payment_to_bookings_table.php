<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->after('total_amount');
            $table->string('payment_method', 20)->nullable()->after('amount_paid'); // cash | gcash | bank
            $table->string('payment_reference', 100)->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
        });

        // Bookings confirmed before payments were tracked count as paid, so they stay valid.
        DB::table('bookings')
            ->whereIn('status', ['confirmed', 'completed'])
            ->update(['amount_paid' => DB::raw('total_amount'), 'paid_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_method', 'payment_reference', 'paid_at']);
        });
    }
};
