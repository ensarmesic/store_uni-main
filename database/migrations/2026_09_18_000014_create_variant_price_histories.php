<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('offers', fn (Blueprint $table) => $table->timestamp('sizes_checked_at')->nullable());
        Schema::create('variant_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_variant_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->date('observed_on');
            $table->timestamp('recorded_at');
            $table->unique(['offer_variant_id', 'observed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_price_histories');
        Schema::table('offers', fn (Blueprint $table) => $table->dropColumn('sizes_checked_at'));
    }
};
