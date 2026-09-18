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
        Schema::create('war_party_slots', function (Blueprint $table) {
            $table->id();
            $table->string('map_slug');
            $table->integer('room_number');
            $table->integer('party_number');
            $table->integer('slot_number');
            $table->string('member_name')->nullable();
            $table->string('class_job')->nullable();
            $table->string('role')->default('สมาชิกตี้');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['map_slug', 'room_number', 'party_number', 'slot_number'], 'war_slot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('war_party_slots');
    }
};
