<?php

namespace Tests\Unit;

use App\Services\NaturalCatalogSearch;
use PHPUnit\Framework\TestCase;

class NaturalCatalogSearchTest extends TestCase
{
    public function test_it_parses_a_bosnian_catalog_request(): void
    {
        $result = (new NaturalCatalogSearch)->parse('crne Nike muske 43 do 180 KM');

        $this->assertSame('NIKE', $result['brand']);
        $this->assertSame('male', $result['gender']);
        $this->assertSame('crna', $result['color']);
        $this->assertSame('43', $result['size']);
        $this->assertSame(180.0, $result['price_max']);
        $this->assertSame('', $result['term']);
    }

    public function test_brand_only_search_does_not_become_a_model_filter(): void
    {
        $result = (new NaturalCatalogSearch)->parse('Nike');
        $this->assertSame('NIKE', $result['brand']);
        $this->assertSame('', $result['term']);
    }

    public function test_model_text_is_preserved_with_a_price_range(): void
    {
        $result = (new NaturalCatalogSearch)->parse('Nike Air Max od 100 BAM do 180,50 KM');
        $this->assertSame('air max', $result['term']);
        $this->assertSame(100.0, $result['price_min']);
        $this->assertSame(180.5, $result['price_max']);
    }
}
