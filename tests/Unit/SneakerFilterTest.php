<?php

namespace Tests\Unit;

use App\Services\ProductImport\SneakerFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SneakerFilterTest extends TestCase
{
    #[DataProvider('products')]
    public function test_only_sneakers_are_accepted(array $product, bool $expected): void
    {
        $this->assertSame($expected, (new SneakerFilter)->accepts($product));
    }

    public static function products(): array
    {
        return [
            [['name' => 'Nike Air Max', 'product_url' => 'https://www.sportvision.ba/patike/123-air-max'], true],
            [['name' => 'Dječije tenisice'], true],
            [['name' => 'HOKA Clifton 10', 'category' => 'Running shoes'], true],
            [['name' => 'Converse Duboke Patike'], true],
            [['name' => 'Skechers Slip-ins Patike'], true],
            [['name' => 'ECCO Cipele', 'category' => 'Obuća'], false],
            [['name' => 'Nike Kopačke', 'category' => 'Patike'], false],
            [['name' => 'Patike / čizme na pertlanje'], false],
            [['name' => 'Nike papuče', 'product_url' => 'https://example.com/patike/123'], false],
            [['name' => 'Adidas Sandale', 'category' => 'Shoes'], false],
            [['name' => 'Nike Running majica'], false],
            [['name' => 'Trail ruksak', 'category' => 'Patike'], false],
            [['name' => 'Leather shoes'], false],
            [['name' => 'Puma Running'], false],
        ];
    }
}
