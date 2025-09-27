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
        Schema::create('new_avanto', function (Blueprint $table) {
            $table->id('avanto_id');
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('date');
            $table->string('location')->nullable();
            $table->float('water_temperature', 8, 1)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('swear_words')->nullable();
            $table->integer('feeling_before')->nullable();
            $table->integer('feeling_after')->nullable();
            $table->string('selfie_path')->nullable();
            $table->boolean('sauna')->nullable();
            $table->integer('sauna_duration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
