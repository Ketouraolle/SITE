<?php

namespace App\Services;

use App\Models\Occasion;
use App\Models\Offer;

/**
 * Periodes en cours et meilleure offre applicable a chaque produit / pack.
 *
 * Le resultat est calcule une fois par jour et par processus (deux requetes)
 * puis relu en memoire : afficher une grille de produits ne declenche pas une
 * requete par carte. Toute modification d'une periode ou d'une offre vide ce
 * cache (voir les evenements des modeles Occasion et Offer).
 */
class Promotions
{
    /**
     * @var array{date: string, occasions: array<int, int>, products: array<int, Offer>, packs: array<int, Offer>}|null
     */
    private ?array $state = null;

    public function flush(): void
    {
        $this->state = null;
    }

    /**
     * @return array<int, int>
     */
    public function runningOccasionIds(): array
    {
        return $this->state()['occasions'];
    }

    /**
     * Vrai si l'article n'est lie a aucune periode, ou si sa periode est en cours.
     */
    public function isOccasionRunning(?int $occasionId): bool
    {
        return $occasionId === null || in_array($occasionId, $this->runningOccasionIds(), true);
    }

    public function productOffer(int $productId): ?Offer
    {
        return $this->state()['products'][$productId] ?? null;
    }

    public function packOffer(int $packId): ?Offer
    {
        return $this->state()['packs'][$packId] ?? null;
    }

    /**
     * @return array{date: string, occasions: array<int, int>, products: array<int, Offer>, packs: array<int, Offer>}
     */
    private function state(): array
    {
        $today = now()->toDateString();

        if ($this->state !== null && $this->state['date'] === $today) {
            return $this->state;
        }

        $occasions = Occasion::running()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $offers = Offer::with(['occasion', 'products:id', 'packs:id'])
            ->where('is_active', true)
            ->get()
            ->filter(fn (Offer $offer) => $offer->isRunning());

        $products = [];
        $packs = [];

        // Plusieurs offres sur le meme article : la plus avantageuse l'emporte.
        foreach ($offers as $offer) {
            foreach ($offer->products as $product) {
                if (($products[$product->id] ?? null)?->discount_percent < $offer->discount_percent) {
                    $products[$product->id] = $offer;
                }
            }

            foreach ($offer->packs as $pack) {
                if (($packs[$pack->id] ?? null)?->discount_percent < $offer->discount_percent) {
                    $packs[$pack->id] = $offer;
                }
            }
        }

        return $this->state = [
            'date' => $today,
            'occasions' => $occasions,
            'products' => $products,
            'packs' => $packs,
        ];
    }
}
