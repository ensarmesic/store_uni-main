<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fit_passport_entries', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 20);
            $table->enum('fit', ['tight', 'just_right', 'wide']);
            $table->timestamps();
            $table->unique(['session_id', 'product_id', 'size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fit_passport_entries');
    }
};
