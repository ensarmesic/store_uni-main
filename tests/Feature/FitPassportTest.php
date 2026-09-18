<?php

namespace Tests\Feature;

use App\Models\{FitGraphEdge, FitPassportEntry, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitPassportTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_add_and_remove_a_fit_passport_entry(): void
    {
        $product = Product::create(['brand' => 'Nike', 'name' => 'Nike Test', 'model' => 'Test', 'slug' => 'nike-test']);

        $response = $this->post(route('fit-passport.store', $product), [
            'size' => '43',
            'fit' => 'just_right',
        ]);

        $response->assertRedirect();
        $entry = FitPassportEntry::firstOrFail();
        $this->assertSame('43', $entry->size);
        $this->assertSame('just_right', $entry->fit);

        $this->delete(route('fit-passport.destroy', $entry))->assertRedirect();
        $this->assertDatabaseCount('fit_passport_entries', 0);
    }

    public function test_two_passport_entries_create_bidirectional_fit_graph_edges(): void
    {
        $first = Product::create(['brand' => 'Nike', 'name' => 'Nike First', 'model' => 'First', 'slug' => 'nike-first']);
        $second = Product::create(['brand' => 'Adidas', 'name' => 'Adidas Second', 'model' => 'Second', 'slug' => 'adidas-second']);

        $this->post(route('fit-passport.store', $first), ['size' => '43', 'fit' => 'just_right'])->assertRedirect();
        $this->post(route('fit-passport.store', $second), ['size' => '43 1/3', 'fit' => 'just_right'])->assertRedirect();

        $this->assertDatabaseCount('fit_graph_edges', 2);
        $this->assertDatabaseHas('fit_graph_edges', ['source_product_id' => $first->id, 'target_product_id' => $second->id, 'target_size' => '43 1/3']);
    }
}
