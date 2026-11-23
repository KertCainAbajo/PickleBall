<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paddles', function (Blueprint $table) {
            // Path relative to public/, e.g. "uploads/paddles/abc.jpg" or "images/paddles/standard.jpg".
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('paddles', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
