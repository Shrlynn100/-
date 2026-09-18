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
        Schema::create('auction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->integer('slot_number');
            $table->integer('page_number');
            $table->string('name')->nullable();
            $table->boolean('book1')->default(false);
            $table->boolean('book2')->default(false);
            $table->boolean('book3')->default(false);
            $table->boolean('book4')->default(false);
            $table->string('winner_name')->nullable();
            $table->bigInteger('final_price')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'slot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_items');
    }
};
