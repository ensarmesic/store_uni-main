<?php

return [
    'ca_bundle' => env('IMPORT_CA_BUNDLE'),
    /* AErchi.ba je namjenski BiH katalog; svaki drugi izvor se odbija. */
    'allowed_store_hosts' => [
        'www.sportvision.ba',
        'www.buzzsneakers.ba',
        'www.sportreality.ba',
        'www.intersport.ba',
        'www.thespot.ba',
        'www.nsport.ba',
        'planika.ba',
        'www.underarmour.ba',
        'www.deichmann.com',
        'astra.ba',
        'www.djaksport.ba',
        'www.officeshoes.ba',
        'www.juventasport.com',
        'sportskaoprema.ba',
        'legea-balkan.ba',
    ],
];
