<?php

return [
    'lens_python' => env('LENS_PYTHON') ?: base_path(PHP_OS_FAMILY === 'Windows' ? '.runtime/lens-venv/Scripts/python.exe' : '.runtime/lens-venv/bin/python'),
    'lens_image_hosts' => ['www.sportvision.ba', 'www.nsport.ba', 'www.buzzsneakers.ba', 'www.sportreality.ba', 'planika.ba', 'asset.deichmann.com', 'www.intersport.ba', 'www.officeshoes.ba', 'www.underarmour.ba'],
    'tesseract_path' => env('TESSERACT_PATH', 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'),
];
