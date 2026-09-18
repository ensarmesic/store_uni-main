<?php

namespace Tests\Unit;

use App\Services\LabelScanner;
use PHPUnit\Framework\TestCase;

class LabelScannerTest extends TestCase
{
    public function test_it_extracts_brand_style_code_and_eu_size_from_ocr_text(): void
    {
        $result = (new LabelScanner)->parse("NIKE\nSTYLE DD1391-100\nEU 43");

        $this->assertSame('NIKE', $result['brand']);
        $this->assertSame('DD1391-100', $result['style_code']);
        $this->assertSame('43', $result['size']);
    }
}
