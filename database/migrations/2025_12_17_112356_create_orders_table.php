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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 16, 8);
            $table->decimal('amount', 16, 8);
            $table->decimal('original_amount', 16, 8); 
            $table->decimal('fee', 16, 8)->default(0);
            $table->integer('status')->default(1); // 1=open, 2=filled, 3=cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
