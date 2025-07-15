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
        // Creo la tabla principal de chats
        Schema::create('chats', function (Blueprint $table) {
            $table->id(); // ID único del chat
            $table->timestamps(); // created_at y updated_at
        });
    
        // Tabla pivote para relacionar usuarios con chats (relación muchos a muchos)
        Schema::create('chat_user', function (Blueprint $table) {
            $table->id(); // ID único de la relación
            $table->foreignId('chat_id')->constrained()->onDelete('cascade'); // Relación al chat
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relación al usuario
            $table->timestamps(); // Por si quiero registrar cuándo se creó la relación
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
