<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shopping_watches', function (Blueprint $table) {
            $table->id(); $table->string('owner_key')->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 20); $table->decimal('budget', 10, 2)->nullable();
            $table->boolean('is_active')->default(true); $table->boolean('last_signal')->default(false);
            $table->unsignedInteger('generation')->default(0); $table->timestamp('last_notified_at')->nullable();
            $table->string('email')->nullable(); $table->timestamp('email_verified_at')->nullable();
            $table->timestamps(); $table->unique(['owner_key', 'product_id', 'size']);
        });
        Schema::create('watch_notifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('shopping_watch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('generation'); $table->json('payload');
            $table->timestamp('read_at')->nullable(); $table->timestamp('emailed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0); $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps(); $table->unique(['shopping_watch_id', 'generation']);
        });
        Schema::create('purchase_feedback', function (Blueprint $table) {
            $table->id(); $table->string('owner_key')->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 20); $table->string('outcome', 20); $table->string('fit', 20);
            $table->timestamps(); $table->unique(['owner_key', 'product_id', 'size']);
        });
        Schema::create('product_interactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('owner_hash', 64); $table->string('kind', 20); $table->date('observed_on');
            $table->unique(['product_id', 'owner_hash', 'kind', 'observed_on'], 'interaction_daily_unique');
            $table->index(['observed_on', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_interactions'); Schema::dropIfExists('purchase_feedback');
        Schema::dropIfExists('watch_notifications'); Schema::dropIfExists('shopping_watches');
    }
};
