<?php

namespace Tests\Feature;

use App\Models\{Favorite, Offer, PriceHistory, Product, Store, User};
use App\Services\{PriceHistoryChart, ProductPriceInsights};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersonalShoppingTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug, string $brand = 'NIKE', string $size = '43', float $price = 150): Product
    {
        $product = Product::create(['slug' => $slug, 'name' => $brand.' '.$slug, 'model' => $slug, 'brand' => $brand]);
        $store = Store::firstOrCreate(['slug' => 'test'], ['name' => 'Test Store', 'website_url' => 'https://example.com']);
        $offer = Offer::create([
            'product_id' => $product->id, 'store_id' => $store->id, 'external_id' => $slug,
            'name_original' => $slug, 'price' => 250, 'old_price' => 300, 'currency' => 'BAM',
            'availability' => 'in_stock', 'is_active' => true, 'product_url' => 'https://example.com/'.$slug, 'last_checked_at' => now(),
        ]);
        $offer->variants()->create(['size' => $size, 'price' => $price, 'availability' => 'in_stock']);
        return $product;
    }

    public function test_favorites_are_idempotent_and_isolated_by_owner(): void
    {
        $product = $this->product('shoe');
        $this->withSession(['shopping_key' => 'guest:first'])->post(route('favorites.store', $product));
        $this->post(route('favorites.store', $product));
        $this->assertDatabaseCount('favorites', 1);
        $this->get('/favorites?size=43')->assertOk()->assertSee('150,00 KM')->assertSee('Prati pad cijene');
        $this->withSession(['shopping_key' => 'guest:second'])->delete(route('favorites.destroy', $product));
        $this->assertDatabaseCount('favorites', 1);
        $this->get('/favorites')->assertViewHas('products', fn ($products) => $products->isEmpty());
    }

    public function test_guest_favorites_merge_into_profile_without_duplicates(): void
    {
        $user = User::create(['username' => 'tester', 'password' => Hash::make('secret123')]);
        $one = $this->product('first');
        $two = $this->product('second');
        Favorite::create(['owner_key' => 'user:'.$user->id, 'product_id' => $one->id]);
        $this->withSession(['shopping_key' => 'guest:merge'])->post(route('favorites.store', $one));
        $this->post(route('favorites.store', $two));
        $this->post(route('account.login'), ['username' => 'tester', 'password' => 'secret123'])->assertRedirect();
        $this->assertDatabaseCount('favorites', 2);
        $this->assertSame(2, Favorite::where('owner_key', 'user:'.$user->id)->count());
    }

    public function test_comparison_has_a_three_model_limit_and_can_remove_models(): void
    {
        $products = collect(range(1, 4))->map(fn ($id) => $this->product('shoe-'.$id));
        foreach ($products->take(3) as $product) $this->post(route('compare.store', $product))->assertRedirect();
        $this->post(route('compare.store', $products->last()))->assertSessionHasErrors('comparison');
        $this->assertCount(3, session('comparison'));
        $this->get('/compare?size=43')->assertOk()->assertSee('150,00 KM')->assertSee('Ušteda 150,00 KM');
        $this->delete(route('compare.destroy', $products->first()))->assertRedirect();
        $this->post(route('compare.store', $products->last()))->assertRedirect();
        $this->assertCount(3, session('comparison'));
    }

    public function test_missing_size_does_not_show_a_misleading_price_or_buy_link(): void
    {
        $product = $this->product('shoe');
        $this->post(route('compare.store', $product));
        $this->get('/compare?size=49')->assertOk()->assertSee('Nedostupno')->assertDontSee('250,00 KM');
        $this->get(route('product.show', ['slug' => $product->slug, 'size' => '49']))->assertOk()->assertSee('Broj nije dostupan')->assertDontSee('Najbolja ponuda');
        $insights = app(ProductPriceInsights::class)->for($product->fresh('offers.variants'), '49');
        $this->assertNull($insights['current']);
    }

    public function test_preferences_apply_variant_price_budget_and_are_not_shared_between_visitors(): void
    {
        $nike = $this->product('nike', 'NIKE', '43', 150);
        $adidas = $this->product('adidas', 'ADIDAS', '44', 140);
        $this->post(route('shopping.preferences.store'), ['size' => '43', 'brands' => ['NIKE'], 'price_max' => 160])->assertRedirect();
        $this->get('/catalog?personal=1')->assertOk()->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$nike->id] && (float) $products->first()->display_min_price === 150.0);
        $this->get('/catalog')->assertViewHas('products', fn ($products) => $products->total() === 2);
        $this->post(route('shopping.preferences.store'), ['size' => '44', 'brands' => ['ADIDAS'], 'price_max' => 160]);
        $this->get('/catalog?personal=1')->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$adidas->id]);
    }

    public function test_personal_brand_filters_are_part_of_cache_key(): void
    {
        $nike = $this->product('nike', 'NIKE');
        $adidas = $this->product('adidas', 'ADIDAS');
        $this->withSession(['shopping_preferences' => ['brands' => ['NIKE']]])->get('/catalog?personal=1')->assertViewHas('products', fn ($products) => $products->first()->id === $nike->id);
        $this->withSession(['shopping_preferences' => ['brands' => ['ADIDAS']]])->get('/catalog?personal=1')->assertViewHas('products', fn ($products) => $products->first()->id === $adidas->id);
    }

    public function test_profile_preferences_persist_and_can_be_cleared(): void
    {
        $user = User::create(['username' => 'tester', 'password' => 'hashed']);
        $this->withSession(['user_id' => $user->id])->post(route('shopping.preferences.store'), ['size' => '42,5', 'price_max' => 200])->assertRedirect();
        $this->assertSame('42.5', $user->fresh()->shopping_preferences['size']);
        $this->get('/my-preferences')->assertOk()->assertSee('42.5');
        $this->post(route('shopping.preferences.store'), [])->assertRedirect();
        $this->get('/catalog?personal=1')->assertRedirect(route('shopping.preferences'));
    }

    public function test_history_keeps_stores_separate_and_respects_period(): void
    {
        $product = $this->product('history');
        $offer = $product->offers()->first();
        $otherStore = Store::create(['slug' => 'other', 'name' => 'Other Store', 'website_url' => 'https://example.org']);
        $other = $offer->replicate();
        $other->store_id = $otherStore->id;
        $other->save();
        foreach ([[$offer, 60, 220], [$offer, 10, 200], [$offer, 0, 190], [$other, 0, 170]] as [$source, $days, $price]) {
            PriceHistory::create(['offer_id' => $source->id, 'price' => $price, 'recorded_at' => now()->subDays($days)]);
        }
        $chart = app(PriceHistoryChart::class)->for($product->fresh('offers'), 30);
        $this->assertCount(2, $chart['series']);
        $this->assertTrue($chart['hasTrend']);
        $this->assertSame(200.0, $chart['high']);
        $this->assertSame(170.0, $chart['low']);
        $this->get(route('product.show', ['slug' => $product->slug, 'days' => 90]))->assertOk()->assertSee('Other Store')->assertSee('220,00 KM');
    }

    public function test_one_history_point_displays_explanation_not_a_blank_chart(): void
    {
        $product = $this->product('history');
        PriceHistory::create(['offer_id' => $product->offers()->first()->id, 'price' => 190, 'recorded_at' => now()]);
        $this->get(route('product.show', $product->slug))->assertOk()->assertSee('Još nema dovoljno podataka za trend.')->assertDontSee('class="history-svg"', false);
    }
}
