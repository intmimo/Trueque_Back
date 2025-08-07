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
        Schema::create('ratings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
        $table->tinyInteger('stars'); // de 1 a 5
        $table->timestamps();
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
