<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->index('product_id', 'offers_product_id_index');
            $table->index(['product_id', 'price'], 'offers_product_price_index');
            $table->index(['product_id', 'availability'], 'offers_product_availability_index');
        });

        Schema::table('offer_variants', function (Blueprint $table) {
            $table->index(['size', 'availability', 'offer_id'], 'offer_variants_filter_index');
        });

        Schema::table('price_histories', function (Blueprint $table) {
            $table->index(['offer_id', 'recorded_at'], 'price_histories_offer_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex('offers_product_id_index');
            $table->dropIndex('offers_product_price_index');
            $table->dropIndex('offers_product_availability_index');
        });

        Schema::table('offer_variants', function (Blueprint $table) {
            $table->dropIndex('offer_variants_filter_index');
        });

        Schema::table('price_histories', function (Blueprint $table) {
            $table->dropIndex('price_histories_offer_date_index');
        });
    }
};
