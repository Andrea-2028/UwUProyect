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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->text('description', 500)->nullable();
            $table->date('last_update')->nullable();
            $table->date('release_date')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->string('image')->nullable();
            $table->foreignId('developer_id')->constrained('developers')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
