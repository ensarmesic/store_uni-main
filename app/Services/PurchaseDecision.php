<?php

namespace App\Services;

use App\Models\Product;

class PurchaseDecision
{
    public function __construct(private ProductPriceInsights $prices, private StockPressure $stock, private OfferSelection $selection) {}

    public function for(Product $product, ?string $size, ?array $fit = null): array
    {
        $prices = $this->prices->for($product, $size);
        $stock = $this->stock->for($product, $size);
        $offers = $this->selection->for($product, $size)->filter(fn ($item) => $item['offer']->store->is_active &&
            $item['offer']->last_checked_at?->between(now()->subHours(48), now()) &&
            (! $size || $item['offer']->sizes_checked_at?->between(now()->subHours(48), now())));
        $best = $offers->first();
        $current = $best['price'] ?? null;
        $aboveLow = $current && $prices['low_90'] ? round(($current / $prices['low_90'] - 1) * 100, 1) : null;
        $enough = $prices['observed_days_90'] >= 3 && $prices['history_span_days'] >= 7;
        $status = 'insufficient';
        $title = 'Još nema dovoljno podataka za odluku';
        $reason = 'Potrebne su cijene tvog broja iz najmanje tri različita dana, s rasponom od sedam dana.';
        if (! $size) {
            $status = 'choose_size'; $title = 'Prvo odaberi svoj EU broj';
            $reason = 'Cijena i dostupnost mogu biti različite za svaki broj.';
        } elseif ($fit && ($fit['suitable'] ?? true) === false && $fit['size'] === $size) {
            $status = 'check_fit'; $title = 'Prvo provjeri kako ti model odgovara';
            $reason = 'U tvom Fit DNA zapisu ovaj broj nije odgovarao. Povoljna cijena to ne mijenja.';
        } elseif (! $current) {
            $status = 'unavailable'; $title = 'Nema svježe potvrđene ponude za tvoj broj';
            $reason = 'Provjeri ponudu trgovine ili uključi praćenje povratka veličine. Za odluku koristimo provjere iz posljednjih 48 sati.';
        } elseif ($enough && $aboveLow !== null) {
            if ($aboveLow <= 5 || ($aboveLow <= 10 && ($stock['score'] ?? 0) >= 50)) {
                $status = 'buy'; $title = 'Kupovina sada ima smisla';
                $reason = 'Cijena tvog broja je najviše '.($aboveLow <= 5 ? '5' : '10').'% iznad najniže zabilježene cijene u posljednjih 90 dana.';
                if (($stock['score'] ?? 0) >= 50) $reason .= ' Dostupnost se smanjuje i u uporedivim trgovinama.';
            } elseif (($stock['score'] ?? 0) >= 50) {
                $status = 'consider'; $title = 'Broj nestaje, ali cijena nije pri minimumu';
                $reason = 'Ako ti je ovaj model važan, provjeri ponude sada. Ako možeš čekati, prati cijenu; niža cijena nije garantovana.';
            } elseif ($aboveLow >= 15) {
                $status = 'wait'; $title = 'Ako ti nije hitno, vrijedi pratiti cijenu';
                $reason = 'Cijena je najmanje 15% iznad zabilježenog minimuma za tvoj broj. To nije prognoza budućeg sniženja.';
            } else {
                $status = 'neutral'; $title = 'Nema jasne prednosti kupovine sada';
                $reason = 'Cijena nije blizu minimuma, a podaci ne pokazuju jak pritisak na dostupnost.';
            }
        }
        return compact('status', 'title', 'reason', 'prices', 'stock', 'fit', 'size', 'current', 'aboveLow', 'enough', 'best');
    }
}
