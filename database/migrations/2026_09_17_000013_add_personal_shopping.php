<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->string('owner_key', 80);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['owner_key', 'product_id']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->json('shopping_preferences')->nullable());
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('shopping_preferences'));
    }
};
