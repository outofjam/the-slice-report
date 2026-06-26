<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pizza_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('pizza_place_id')->constrained('pizza_places');
            $table->foreignUuid('list_id')->constrained('lists');
            $table->decimal('price');
            $table->char('currency', 3);
            $table->decimal('rating', 4, 1);
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'pizza_place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pizza_ratings');
    }
};
