<?php

return [
    // Only add verified BAM delivery terms valid throughout BiH for one pair.
    // 'slug' => ['flat_fee' => 8, 'free_from' => 200, 'free_pickup' => false,
    //            'verified_at' => 'YYYY-MM-DD', 'source_url' => 'https://merchant/...'],
    // No assumptions: unknown or older-than-90-day rules display an unknown total.
    'stores' => [
        'sportvision' => ['flat_fee' => 9.95, 'free_above' => 99, 'unknown_at' => 99, 'free_pickup' => true,
            'pickup_note' => 'Click & Collect uz online plaćanje karticom i potvrdu trgovine.',
            'verified_at' => '2026-09-18', 'source_url' => 'https://www.sportvision.ba/uslovi-isporuke'],
        'buzz' => ['flat_fee' => 9.95, 'free_above' => 99, 'unknown_at' => 99, 'free_pickup' => true,
            'pickup_note' => 'Click & Collect uz online plaćanje karticom i potvrdu trgovine.',
            'verified_at' => '2026-09-18', 'source_url' => 'https://www.buzzsneakers.ba/isporuka'],
    ],
];
