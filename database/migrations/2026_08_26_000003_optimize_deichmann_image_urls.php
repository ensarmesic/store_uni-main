<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $storeId = DB::table('stores')->where('slug', 'deichmann')->value('id');
        if (!$storeId) return;

        DB::table('offers')
            ->where('store_id', $storeId)
            ->where('image_url', 'like', '%asset.deichmann.com/images/%')
            ->orderBy('id')
            ->chunkById(250, function ($offers) {
                foreach ($offers as $offer) {
                    $url = preg_replace(
                        '~asset\.deichmann\.com/images/[^/]+/~',
                        'asset.deichmann.com/images/f_auto,q_75,w_900,ar_4:3,c_fill,g_auto/',
                        $offer->image_url,
                        1
                    );

                    if ($url !== $offer->image_url) {
                        DB::table('offers')->where('id', $offer->id)->update(['image_url' => $url]);
                    }
                }
            });
    }

    public function down(): void
    {
        $storeId = DB::table('stores')->where('slug', 'deichmann')->value('id');
        if (!$storeId) return;

        DB::table('offers')->where('store_id', $storeId)
            ->where('image_url', 'like', '%asset.deichmann.com/images/f_auto,q_75,w_900,ar_4:3,c_fill,g_auto/%')
            ->update([
                'image_url' => DB::raw("REPLACE(image_url, '/images/f_auto,q_75,w_900,ar_4:3,c_fill,g_auto/', '/images/f_auto,q_100/')"),
            ]);
    }
};
