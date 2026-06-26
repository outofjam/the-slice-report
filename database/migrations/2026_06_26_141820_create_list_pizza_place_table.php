<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_pizza_place', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('list_id')->constrained('lists')->cascadeOnDelete();
            $table->foreignUuid('pizza_place_id')->constrained('pizza_places')->cascadeOnDelete();
            $table->foreignUuid('added_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_pizza_place');
    }
};
