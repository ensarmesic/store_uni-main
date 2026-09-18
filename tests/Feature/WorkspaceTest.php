<?php

namespace Tests\Feature;

use App\Models\{FitPassportEntry, Offer, Product, ProductAlert, Store, User};
use App\Services\LabelScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $code = 'DD1391-100', bool $active = true): Product
    {
        $product = Product::create(['brand' => 'NIKE', 'name' => 'Nike Dunk '.$code, 'model' => 'Dunk', 'slug' => strtolower($code), 'mpn' => $code]);
        $store = Store::firstOrCreate(['slug' => 'test'], ['name' => 'Test', 'website_url' => 'https://example.com']);
        $offer = Offer::create([
            'product_id' => $product->id, 'store_id' => $store->id, 'external_id' => $code,
            'name_original' => $product->name, 'price' => 200, 'currency' => 'BAM',
            'availability' => 'in_stock', 'is_active' => $active,
            'product_url' => 'https://example.com/'.$code, 'last_checked_at' => now(),
        ]);
        $offer->variants()->create(['size' => '43', 'availability' => 'in_stock', 'price' => 159.90]);

        return $product;
    }

    public function test_personal_tools_render_with_shared_navigation(): void
    {
        foreach (['/account', '/account?mode=register', '/fit-passport', '/alerts', '/scanner'] as $url) {
            $this->get($url)->assertOk()->assertSee('/css/workspace.css')->assertSee('Lični alati')->assertSee('Pratim cijene');
        }
    }

    public function test_registration_reports_case_insensitive_duplicate_and_keeps_registration_mode(): void
    {
        User::create(['username' => 'tester', 'password' => 'secret123']);
        $this->from('/account?mode=register')->post(route('account.register'), [
            'username' => 'TESTER', 'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('username')->assertSessionHasInput('form', 'register');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_failure_keeps_username_without_flashing_password(): void
    {
        $this->from('/account')->post(route('account.login'), [
            'username' => 'unknown', 'password' => 'private-password', 'form' => 'login',
        ])->assertSessionHasErrors('username')->assertSessionHasInput('username', 'unknown');
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_profile_shows_real_counts_and_fit_works_on_another_device(): void
    {
        $user = User::create(['username' => 'tester', 'password' => 'secret123']);
        $product = $this->product();
        FitPassportEntry::create(['session_id' => 'old-device', 'user_id' => $user->id, 'product_id' => $product->id, 'size' => '43', 'fit' => 'just_right']);
        $this->withSession(['user_id' => $user->id])->get('/account')->assertOk()->assertViewHas('profileStats', fn ($stats) => $stats['fits'] === 1);
        $this->get(route('product.show', $product->slug))->assertOk()->assertViewHas('fit', fn ($fit) => $fit['size'] === '43');
        $this->post(route('fit-passport.store', $product), ['size' => '43', 'fit' => 'tight'])->assertRedirect();
        $this->assertDatabaseCount('fit_passport_entries', 1);
        $this->assertDatabaseHas('fit_passport_entries', ['user_id' => $user->id, 'fit' => 'tight']);
    }

    public function test_passport_search_supports_multiple_words(): void
    {
        $product = $this->product();
        $this->get('/fit-passport?q=Nike+Dunk')->assertOk()->assertViewHas('candidates', fn ($products) => $products->contains('id', $product->id))->assertSee('Sačuvaj veličinu');
        $this->get('/fit-passport?q=missing')->assertOk()->assertSee('Nema tog modela');
    }

    public function test_manual_scanner_matches_the_code_instead_of_unrelated_brand_products(): void
    {
        $matched = $this->product();
        $this->product('DD9999-100');
        $this->post(route('scanner.scan'), ['label_text' => 'NIKE DD1391-100 EU 43'])->assertOk()
            ->assertViewHas('result', fn ($result) => $result['matches']->pluck('id')->all() === [$matched->id])
            ->assertSee('Pronašli smo podudaranje.');
        $this->post(route('scanner.scan'), ['label_text' => 'NIKE EU 43'])->assertOk()
            ->assertViewHas('result', fn ($result) => $result['matches']->isEmpty());
    }

    public function test_scanner_excludes_inactive_products_and_explains_ocr_failure(): void
    {
        $this->product('DD1391-100', false);
        $this->post(route('scanner.scan'), ['label_text' => 'NIKE DD1391-100 EU 43'])->assertOk()
            ->assertViewHas('result', fn ($result) => $result['matches']->isEmpty());
        $this->mock(LabelScanner::class, function ($mock) {
            $mock->shouldReceive('scan')->once()->andThrow(new \RuntimeException('Internal executable path'));
        });
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('label.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $this->from('/scanner')->post(route('scanner.scan'), ['image' => $file])->assertSessionHasErrors('image');
        $this->get('/scanner')->assertSee('unesi tekst s etikete ispod')->assertDontSee('Internal executable path');
    }

    public function test_alert_page_checks_size_price_and_does_not_duplicate_repeated_requests(): void
    {
        $product = $this->product();
        $payload = ['type' => 'price', 'target_price' => 160, 'size' => '43'];
        $this->post(route('alerts.store', $product), $payload)->assertRedirect();
        $this->post(route('alerts.store', $product), $payload)->assertRedirect();
        $this->assertDatabaseCount('product_alerts', 1);
        $this->get('/alerts')->assertOk()->assertSee('159,90')->assertSee('Uslov ispunjen');
        $this->assertNotNull(ProductAlert::first()->triggered_at);
        $this->post(route('alerts.store', $product), $payload)->assertRedirect();
        $this->assertDatabaseCount('product_alerts', 1);
        $this->assertDatabaseHas('product_alerts', ['is_active' => true, 'triggered_at' => null]);
    }

    public function test_disabled_alert_is_not_shown_as_triggered_and_other_owners_are_excluded(): void
    {
        $product = $this->product();
        $this->post(route('alerts.store', $product), ['type' => 'price', 'target_price' => 160, 'size' => '43']);
        $alert = ProductAlert::firstOrFail();
        $this->delete(route('alerts.destroy', $alert));
        $other = ProductAlert::create(['session_key' => 'other', 'product_id' => $product->id, 'type' => 'price', 'target_price' => 300]);
        $this->get('/alerts')->assertOk()->assertSee('Ugašeno')->assertViewHas('alerts', fn ($alerts) => $alerts->count() === 1);
        $this->assertNull($alert->fresh()->triggered_at);
        $this->assertTrue($other->fresh()->is_active);
    }
}
