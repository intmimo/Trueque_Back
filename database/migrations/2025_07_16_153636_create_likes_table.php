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
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreingId('user_id')->constraiend ('users') ->onDelete('cascada');
            $table->foreingId('product_id')->constraiend ('products') ->onDelete('cascada');
            $table->timestamps();

                    //Evitar que el ususario pueda dar mas de un like
            $table->unique(['user_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
