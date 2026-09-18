<?php

namespace App\Services;

use App\Models\FitPassportEntry;
use App\Models\FitGraphEdge;
use App\Models\Product;
use Illuminate\Support\Collection;

class FitRecommendation
{
    public function for(Product $product, string $sessionKey, ?int $userId = null): ?array
    {
        $entries = FitPassportEntry::query()
            ->where(fn ($query) => $query->where('session_id', $sessionKey)->when($userId, fn ($owner) => $owner->orWhere('user_id', $userId)))
            ->with('product:id,brand,model,name')
            ->get();

        $exact = $entries->where('product_id', $product->id)->sortByDesc('updated_at')->first();
        if ($exact) {
            return [
                'size' => $exact->size,
                'confidence' => 100,
                'reason' => 'Na osnovu tvog zapisa za ovaj model: '.($exact->fit === 'just_right' ? 'odgovara taman.' : ($exact->fit === 'tight' ? 'bio je tijesan.' : 'bio je širok.')),
            ];
        }

        $sourceIds = $entries->pluck('product_id')->unique()->values();
        if ($sourceIds->isNotEmpty()) {
            $edges = FitGraphEdge::query()
                ->where('target_product_id', $product->id)
                ->where(fn ($query) => $query->where('session_key', $sessionKey)->when($userId, fn ($owner) => $owner->orWhere('user_id', $userId)))
                ->whereIn('source_product_id', $sourceIds)
                ->get();
            if ($edges->isNotEmpty()) {
                $scores = $edges->groupBy('target_size')->map(fn (Collection $group) => $group->sum(
                    fn ($edge) => $edge->target_fit === 'just_right' ? 1 : .5
                ));
                $size = $scores->sortDesc()->keys()->first();
                $sample = $edges->where('target_size', $size);

                return [
                    'size' => (string) $size,
                    'confidence' => min(92, 65 + ($sample->count() * 5)),
                    'reason' => 'Na osnovu '.$sample->count().' anonimnih Fit Graph odnosa sa tvojim poznatim modelima.',
                ];
            }
        }

        $brandEntries = $entries->filter(fn ($entry) => $entry->product?->brand === $product->brand);
        $community = false;
        if ($brandEntries->isEmpty()) {
            $brandEntries = FitPassportEntry::query()
                ->whereHas('product', fn ($query) => $query->where('brand', $product->brand))
                ->with('product:id,brand,model,name')
                ->get();
            $community = $brandEntries->isNotEmpty();
        }
        if ($brandEntries->isEmpty()) return null;

        $scores = $brandEntries->groupBy('size')->map(fn (Collection $group) => $group->sum(
            fn ($entry) => $entry->fit === 'just_right' ? 1 : .5
        ));
        $size = $scores->sortDesc()->keys()->first();
        $sample = $brandEntries->where('size', $size);
        $confidence = $community
            ? min(85, 45 + ($sample->count() * 2) + ($sample->where('fit', 'just_right')->count() * 2))
            : min(95, 55 + ($sample->count() * 10) + ($sample->where('fit', 'just_right')->count() * 5));

        return [
            'size' => (string) $size,
            'confidence' => $confidence,
            'reason' => $community
                ? 'Na osnovu '.$brandEntries->count().' anonimnih Fit Passport profila za brend '.$product->brand.'.'
                : 'Na osnovu '.$brandEntries->count().' tvojih Fit Passport zapisa za brend '.$product->brand.'.',
        ];
    }
}
