<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('offers')
            ->where('image_url', 'like', '%asset.deichmann.com/images/f_auto,q_75,w_900,c_limit/%')
            ->update([
                'image_url' => DB::raw("REPLACE(image_url, '/images/f_auto,q_75,w_900,c_limit/', '/images/f_auto,q_75,w_900,ar_4:3,c_fill,g_auto/')"),
            ]);
    }

    public function down(): void
    {
        DB::table('offers')
            ->where('image_url', 'like', '%asset.deichmann.com/images/f_auto,q_75,w_900,ar_4:3,c_fill,g_auto/%')
            ->update([
                'image_url' => DB::raw("REPLACE(image_url, '/images/f_auto,q_75,w_900,ar_4:3,c_fill,g_auto/', '/images/f_auto,q_75,w_900,c_limit/')"),
            ]);
    }
};
