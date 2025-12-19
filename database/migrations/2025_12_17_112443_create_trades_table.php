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
    Schema::create('trades', function (Blueprint $table) {
        $table->id();
        $table->foreignId('buy_order_id')->constrained('orders')->onDelete('cascade');
        $table->foreignId('sell_order_id')->constrained('orders')->onDelete('cascade');
        $table->string('symbol'); // It stays right here
        $table->decimal('price', 16, 2);
        $table->decimal('amount', 16, 8);
        $table->decimal('commission', 16, 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
