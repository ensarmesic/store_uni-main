<?php

namespace Tests\Unit;

use App\Models\{FitPassportEntry, Product, User};
use App\Services\FitRecommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_model_entry_has_full_confidence(): void
    {
        $product = Product::create(['brand' => 'Nike', 'name' => 'Nike Test', 'model' => 'Test', 'slug' => 'nike-test']);
        FitPassportEntry::create(['session_id' => 'session-a', 'product_id' => $product->id, 'size' => '43', 'fit' => 'just_right']);

        $recommendation = app(FitRecommendation::class)->for($product, 'session-a');

        $this->assertSame('43', $recommendation['size']);
        $this->assertSame(100, $recommendation['confidence']);
    }

    public function test_same_brand_entries_produce_a_transparent_fallback(): void
    {
        $known = Product::create(['brand' => 'Adidas', 'name' => 'Adidas Known', 'model' => 'Known', 'slug' => 'adidas-known']);
        $target = Product::create(['brand' => 'Adidas', 'name' => 'Adidas Target', 'model' => 'Target', 'slug' => 'adidas-target']);
        FitPassportEntry::create(['session_id' => 'session-b', 'product_id' => $known->id, 'size' => '43 1/3', 'fit' => 'just_right']);

        $recommendation = app(FitRecommendation::class)->for($target, 'session-b');

        $this->assertSame('43 1/3', $recommendation['size']);
        $this->assertStringContainsString('Adidas', $recommendation['reason']);
    }

    public function test_community_brand_profiles_provide_a_lower_confidence_fallback(): void
    {
        $known = Product::create(['brand' => 'Puma', 'name' => 'Puma Known', 'model' => 'Known', 'slug' => 'puma-known']);
        $target = Product::create(['brand' => 'Puma', 'name' => 'Puma Target', 'model' => 'Target', 'slug' => 'puma-target']);
        FitPassportEntry::create(['session_id' => 'another-session', 'product_id' => $known->id, 'size' => '42', 'fit' => 'just_right']);

        $recommendation = app(FitRecommendation::class)->for($target, 'empty-session');

        $this->assertSame('42', $recommendation['size']);
        $this->assertLessThan(100, $recommendation['confidence']);
        $this->assertStringContainsString('anonimnih', $recommendation['reason']);
    }

    public function test_account_linked_passport_entries_are_used_after_login(): void
    {
        $user = User::create(['username' => 'fituser', 'password' => 'hashed']);
        $known = Product::create(['brand' => 'Nike', 'name' => 'Nike Known', 'model' => 'Known', 'slug' => 'nike-account-known']);
        $target = Product::create(['brand' => 'Nike', 'name' => 'Nike Target', 'model' => 'Target', 'slug' => 'nike-account-target']);
        FitPassportEntry::create(['session_id' => 'old-session', 'user_id' => $user->id, 'product_id' => $known->id, 'size' => '43', 'fit' => 'just_right']);

        $recommendation = app(FitRecommendation::class)->for($target, 'new-session', $user->id);

        $this->assertSame('43', $recommendation['size']);
    }
}
