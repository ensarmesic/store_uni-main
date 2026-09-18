<?php

namespace App\Services;

class NaturalCatalogSearch
{
    public function parse(string $input): array
    {
        $original = trim($input);
        $text = mb_strtolower($original);
        $result = ['brand' => null, 'gender' => null, 'color' => null, 'size' => null, 'price_min' => null, 'price_max' => null, 'term' => $original];

        if (preg_match('/\bdo\s+(\d+(?:[,.]\d+)?)(?:\s*(?:km|bam)\b)?/u', $text, $match)) {
            $result['price_max'] = (float) str_replace(',', '.', $match[1]);
            $text = str_replace($match[0], ' ', $text);
        }
        if (preg_match('/\bod\s+(\d+(?:[,.]\d+)?)(?:\s*(?:km|bam)\b)?/u', $text, $match)) {
            $result['price_min'] = (float) str_replace(',', '.', $match[1]);
            $text = str_replace($match[0], ' ', $text);
        }
        if (preg_match('/\b(?:eu\s*)?(4[0-9](?:[,.]\d)?)\b/u', $text, $match)) {
            $result['size'] = str_replace(',', '.', $match[1]);
            $text = str_replace($match[0], ' ', $text);
        }

        foreach (['ženske' => 'female', 'zenske' => 'female', 'ženska' => 'female', 'muske' => 'male', 'muške' => 'male', 'muska' => 'male', 'muška' => 'male', 'djeca' => 'kids', 'djeca' => 'kids'] as $word => $gender) {
            if (str_contains($text, $word)) {
                $result['gender'] = $gender;
                $text = str_replace($word, ' ', $text);
                break;
            }
        }

        foreach (['crne' => 'crna', 'crna' => 'crna', 'bijele' => 'bijela', 'bijela' => 'bijela', 'black' => 'black', 'white' => 'white'] as $word => $color) {
            if (str_contains($text, $word)) {
                $result['color'] = $color;
                $text = str_replace($word, ' ', $text);
                break;
            }
        }

        $brands = ['new balance', 'under armour', 'nike', 'adidas', 'asics', 'puma', 'skechers', 'reebok', 'salomon', 'mizuno', 'hoka', 'converse', 'ecco', 'geox', 'fila', 'joma', 'umbro', 'diadora'];
        usort($brands, fn ($left, $right) => strlen($right) <=> strlen($left));
        foreach ($brands as $brand) {
            if (str_contains($text, $brand)) {
                $result['brand'] = strtoupper($brand);
                $text = str_replace($brand, ' ', $text);
                break;
            }
        }

        $result['term'] = trim(preg_replace('/\s+/', ' ', $text));

        return $result;
    }
}
