<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('avatars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('hair_style')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('skin_color')->nullable();

            $table->unsignedBigInteger('clothes_id')->nullable();
            $table->foreign('clothes_id')->references('id')->on('items')->onDelete('set null');

            $table->unsignedBigInteger('accessory_id')->nullable();
            $table->foreign('accessory_id')->references('id')->on('items')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('avatars');
        
    }
};

