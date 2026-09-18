<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('last_checked_at');
            $table->timestamp('missing_since')->nullable()->after('last_seen_at');
            $table->boolean('is_active')->default(true)->after('missing_since');
            $table->index(['store_id', 'is_active', 'last_seen_at']);
        });

        Schema::create('offer_variant_availability_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_variant_id')->constrained()->cascadeOnDelete();
            $table->string('availability');
            $table->timestamp('recorded_at');
            $table->index(['offer_variant_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_variant_availability_histories');
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'is_active', 'last_seen_at']);
            $table->dropColumn(['last_seen_at', 'missing_since', 'is_active']);
        });
    }
};
