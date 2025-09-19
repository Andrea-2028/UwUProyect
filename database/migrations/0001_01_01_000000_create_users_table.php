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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name',100);
            $table->string('last_name', 100);
            $table->string('phone',10);
            $table->string('email',100)->unique();
            $table->string('password', 500);
            $table->enum('status', ['active','inactive'])->default('active');
            $table->string('2facode', 6)->nullable();
            $table->rememberToken();
            $table->timestamps();            
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email',100)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
    }
};
