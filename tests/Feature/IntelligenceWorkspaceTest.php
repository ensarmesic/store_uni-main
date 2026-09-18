<?php

namespace Tests\Feature;

use App\Models\{Offer, Product, ShoppingWatch, Store, WatchNotification, VariantPriceHistory, PurchaseFeedback, User};
use App\Services\{ShoppingIntent, TotalCost, WatchChecker};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Artisan, DB, Mail, URL};
use Tests\TestCase;

class IntelligenceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug = 'test', float $price = 100): Product
    {
        $product = Product::create(['slug' => $slug, 'name' => 'NIKE Air Max 270', 'model' => 'Air Max 270', 'brand' => 'NIKE', 'color' => 'crna']);
        $store = Store::firstOrCreate(['slug' => 'test'], ['name' => 'Store', 'website_url' => 'https://example.com']);
        $offer = Offer::create(['product_id' => $product->id, 'store_id' => $store->id, 'external_id' => $slug,
            'name_original' => $product->name, 'price' => 250, 'currency' => 'BAM', 'is_active' => true,
            'availability' => 'in_stock', 'last_checked_at' => now(), 'sizes_checked_at' => now(), 'product_url' => 'https://example.com/shoe']);
        $variant = $offer->variants()->create(['size' => '43', 'price' => $price, 'availability' => 'in_stock']);
        foreach ([10,5,0] as $days) VariantPriceHistory::create(['offer_variant_id' => $variant->id, 'price' => 100, 'observed_on' => now()->subDays($days)->toDateString(), 'recorded_at' => now()->subDays($days)]);
        return $product;
    }

    public function test_watch_is_idempotent_budget_aware_and_notifications_are_private(): void
    {
        $product = $this->product();
        $this->withSession(['shopping_key' => 'guest:one'])->post(route('watch.store',$product), ['size'=>'43','budget'=>99])->assertRedirect();
        $watch = ShoppingWatch::firstOrFail();
        $checker = app(WatchChecker::class);
        $this->assertFalse($checker->check($watch));
        $this->post(route('watch.store',$product), ['size'=>'43','budget'=>120]);
        $this->assertDatabaseCount('shopping_watches', 1);
        $this->assertTrue($checker->check($watch->fresh()));
        $this->assertFalse($checker->check($watch->fresh()));
        $this->assertDatabaseCount('watch_notifications', 1);
        $this->get('/watch')->assertOk()->assertSee('Kupovina sada ima smisla');
        $notification = WatchNotification::first();
        $this->withSession(['shopping_key'=>'guest:other'])->post(route('watch.read',$notification))->assertForbidden();
        $this->delete(route('watch.destroy',$watch))->assertForbidden();
        $this->get('/watch')->assertOk()->assertViewHas('notifications', fn ($items) => $items->isEmpty());
    }

    public function test_watch_only_notifies_again_after_a_new_signal_and_cooldown(): void
    {
        $product = $this->product();
        $watch = ShoppingWatch::create(['owner_key'=>'guest:one','product_id'=>$product->id,'size'=>'43']);
        $checker = app(WatchChecker::class); $this->assertTrue($checker->check($watch));
        $variant = $product->offers()->first()->variants()->first();
        $variant->update(['price'=>150]); $this->assertFalse($checker->check($watch->fresh()));
        $variant->update(['price'=>100]); $this->assertFalse($checker->check($watch->fresh()));
        $this->travel(25)->hours();
        $this->assertTrue($checker->check($watch->fresh()));
        $this->assertDatabaseCount('watch_notifications', 2);
        $this->travelBack();
    }

    public function test_email_requires_signed_confirmation_and_delivery_is_retried(): void
    {
        $product = $this->product(); config(['mail.default'=>'smtp']); Mail::shouldReceive('raw')->once();
        $this->post(route('watch.store',$product), ['size'=>'43','email'=>'person@example.com'])->assertRedirect();
        $watch = ShoppingWatch::first();
        $this->assertNull($watch->email_verified_at);
        $this->get(route('watch.verify',['watch'=>$watch->id,'email_hash'=>hash('sha256',$watch->email)]))->assertForbidden();
        $signed = URL::temporarySignedRoute('watch.verify',now()->addHour(),['watch'=>$watch->id,'email_hash'=>hash('sha256',$watch->email)]);
        $this->get($signed)->assertRedirect();
        $this->assertNotNull($watch->fresh()->email_verified_at);
        app(WatchChecker::class)->check($watch->fresh());
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Delivery unavailable'));
        Artisan::call('watch:check');
        $notification = WatchNotification::first(); $this->assertNull($notification->emailed_at); $this->assertSame(1,$notification->attempts);
        $this->travel(16)->minutes(); Mail::shouldReceive('raw')->once();
        Artisan::call('watch:check'); $this->assertNotNull($notification->fresh()->emailed_at); $this->travelBack();
    }

    public function test_agent_uses_live_size_price_and_shows_real_interpretation(): void
    {
        $product = $this->product();
        $intent = app(ShoppingIntent::class)->parse('Nosim Nike Air Max 270 broj 43 i taman mi je. Budžet 200 KM. Volim crne ili sive. Mogu čekati.');
        $this->assertSame(200.0,$intent['budget']); $this->assertTrue($intent['can_wait']);
        $this->assertSame($product->id,$intent['anchors'][0]['product_id']);
        $this->post(route('agent.analyze'),['message'=>'Crne patike do 120 KM','size'=>'43'])
            ->assertOk()->assertSee('100,00 KM')->assertViewHas('analysis',fn ($a)=>$a['results']->count()===1);
        $this->post(route('agent.analyze'),['message'=>'Crne patike do 90 KM','size'=>'43'])
            ->assertOk()->assertViewHas('analysis',fn ($a)=>$a['results']->isEmpty());
    }

    public function test_interactions_are_deduplicated_and_drive_ranking(): void
    {
        $one=$this->product('one'); $two=$this->product('two');
        $this->get(route('product.show',$two->slug)); $this->get(route('product.show',$two->slug));
        $this->assertSame(1,DB::table('product_interactions')->where('kind','view')->count());
        $this->get('/catalog')->assertOk()->assertViewHas('popularProducts',fn ($items)=>$items->first()->id===$two->id);
        $this->get(route('offers.visit',$two->offers()->first()))->assertRedirect('https://example.com/shoe');
        $this->assertSame(1,DB::table('product_interactions')->where('kind','outbound')->count());
    }

    public function test_purchase_feedback_is_private_editable_and_community_counts_unique_profiles(): void
    {
        $product=$this->product();
        $this->withSession(['shopping_key'=>'guest:one'])->post(route('purchases.store',$product),['size'=>'43','fit'=>'tight','outcome'=>'returned'])->assertRedirect();
        $this->post(route('purchases.store',$product),['size'=>'43','fit'=>'just_right','outcome'=>'kept']);
        $this->assertDatabaseCount('purchase_feedback',1); $feedback=PurchaseFeedback::first();
        $this->withSession(['shopping_key'=>'guest:other'])->delete(route('purchases.destroy',$feedback))->assertForbidden();
        $this->get('/purchases')->assertOk()->assertViewHas('entries',fn ($entries)=>$entries->isEmpty());
        $this->get(route('product.show',$product->slug))->assertViewHas('community',fn ($c)=>$c['profiles']===1&&$c['kept']===1);
    }

    public function test_total_cost_requires_recent_verified_terms_and_respects_free_shipping_threshold(): void
    {
        $offer=$this->product()->offers()->first(); $service=app(TotalCost::class);
        $this->assertNull($service->for($offer,150)['total']);
        config(['delivery.stores.test'=>['flat_fee'=>8,'free_from'=>200,'free_pickup'=>true,'verified_at'=>now()->toDateString(),'source_url'=>'https://example.com/delivery']]);
        $this->assertSame(158.0,$service->for($offer,150)['total']);
        $this->assertSame(200.0,$service->for($offer,200)['total']);
        config(['delivery.stores.test.verified_at'=>now()->subDays(100)->toDateString()]);
        $this->assertNull($service->for($offer,200)['total']);
    }

    public function test_visual_lens_displays_candidates_and_links_to_decisions_without_claiming_identity(): void
    {
        $product = $this->product();
        $this->mock(\App\Services\VisualSearch::class, function ($mock) use ($product) {
            $mock->shouldReceive('ready')->andReturn(true);
            $mock->shouldReceive('search')->once()->andReturn(['matches'=>collect([$product]), 'scores'=>collect([$product->id=>.8]),
                'indexed'=>10,'visual'=>true,'brand'=>null,'style_code'=>null,'size'=>null,'text'=>'Vizuelni kandidati']);
        });
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('shoe.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $this->post(route('scanner.scan'), ['mode'=>'visual','image'=>$file])->assertOk()
            ->assertSee('Vizuelni kandidati: potvrdi model.')->assertDontSee('Pronašli smo podudaranje.')
            ->assertSee('Provjeri da li je ovo tvoj model');
    }

    public function test_shipping_threshold_is_not_guessed_at_an_ambiguous_boundary(): void
    {
        $offer = $this->product()->offers()->first(); $offer->store->update(['slug'=>'buzz']);
        $service=app(TotalCost::class);
        $this->assertSame(98.95,$service->for($offer,89)['total']);
        $this->assertNull($service->for($offer,99)['total']);
        $this->assertSame(99.01,$service->for($offer,99.01)['total']);
    }
}
