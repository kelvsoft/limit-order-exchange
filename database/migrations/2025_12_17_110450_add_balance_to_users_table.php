<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // /**
    //  * Run the migrations.
    //  */
    // public function up(): void
    // {
    //     Schema::table('users', function (Blueprint $table) {
    //         //
    //     });
    // }

    // /**
    //  * Reverse the migrations.
    //  */
    // public function down(): void
    // {
    //     Schema::table('users', function (Blueprint $table) {
    //         //
    //     });
    // }

    public function up() {
    Schema::table('users', function (Blueprint $table) {
        $table->decimal('balance', 16, 2)->default(0);
    });
}

public function down() {
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('balance');
    });
}

};
