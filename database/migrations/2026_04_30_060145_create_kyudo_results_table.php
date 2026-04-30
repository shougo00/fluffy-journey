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
        Schema::create('kyudo_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 会から離れまでの時間 ms
            $table->integer('kai_time');

            // 会から1秒後の角度
            $table->float('right_elbow_angle')->nullable();
            $table->float('right_armpit_angle')->nullable();
            $table->float('left_armpit_angle')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyudo_results');
    }
};
