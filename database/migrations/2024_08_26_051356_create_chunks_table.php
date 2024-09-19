<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('x');
            $table->integer('z');
            $table->integer('region_id');
            $table->json('heightmap_motion_blocking')->default(null);
            $table->json('heightmap_ocean_floor')->default(null);
            $table->dateTime('last_modified')->nullable();

            $table->unique(['region_id', 'x', 'z'], 'coordinates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
