<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            // Path relative to public/, e.g. "uploads/courts/abc.jpg" or "images/courts/court-1.jpg".
            $table->string('image_path')->nullable()->after('surface_type');
        });
    }

    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
