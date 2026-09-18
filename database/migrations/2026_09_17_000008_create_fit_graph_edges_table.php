<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fit_graph_edges', function (Blueprint $table) {
            $table->id();
            $table->string('session_key')->index();
            $table->foreignId('source_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('source_size', 20);
            $table->foreignId('target_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('target_size', 20);
            $table->enum('target_fit', ['tight', 'just_right', 'wide']);
            $table->timestamps();
            $table->unique(['session_key', 'source_product_id', 'source_size', 'target_product_id', 'target_size']);
            $table->index(['source_product_id', 'target_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fit_graph_edges');
    }
};
