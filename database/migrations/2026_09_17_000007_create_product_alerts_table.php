<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('session_key')->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['price', 'restock']);
            $table->string('size', 20)->nullable();
            $table->decimal('target_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();
            $table->index(['session_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_alerts');
    }
};
