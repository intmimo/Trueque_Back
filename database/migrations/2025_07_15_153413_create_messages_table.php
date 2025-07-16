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
        // Tabla que almacena los mensajes enviados dentro de un chat
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // ID único del mensaje
            $table->foreignId('chat_id')->constrained()->onDelete('cascade'); // Relación al chat
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relación al remitente
            $table->text('content'); // Contenido del mensaje
            $table->timestamp('read_at')->nullable(); // Marca cuándo fue leído (puede ser null)
            $table->timestamps(); // created_at (enviado) y updated_at (editado)
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
