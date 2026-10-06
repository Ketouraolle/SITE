<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Offer;
use App\Models\Pack;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Catalogue de demonstration : produits de beaute, habits et bijoux, avec les
 * periodes commerciales (Noel, Saint-Valentin, Aid...) et quelques offres.
 *
 * Les donnees couvrent tous les etats de l'interface (en stock, bientot
 * epuise, rupture, indisponible, brouillon, article de saison).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Produits de beaute', 'position' => 1, 'description' => 'Soins du visage et du corps, maquillage et parfums.'],
            ['name' => 'Habits', 'position' => 2, 'description' => 'Mode femme et homme : pagne, wax, tenues de fete.'],
            ['name' => 'Bijoux', 'position' => 3, 'description' => 'Colliers, bracelets, boucles d\'oreilles et montres.'],
        ])->mapWithKeys(function (array $attributes) {
            $category = Category::updateOrCreate(
                ['slug' => str($attributes['name'])->slug()->value()],
                [...$attributes, 'is_active' => true],
            );

            return [$attributes['name'] => $category];
        });

        $occasions = $this->seedOccasions();

        $products = [
            // [nom, categorie, prix, prix barre, stock, disponible, publie, vedette, periode, resume]
            ['Creme hydratante karite 200 ml', 'Produits de beaute', 4500, 5200, 30, true, true, true, null, 'Beurre de karite pur et huile de coco, peaux seches.'],
            ['Serum eclat vitamine C 30 ml', 'Produits de beaute', 8500, 10000, 14, true, true, true, null, 'Unifie le teint et ravive l\'eclat en 4 semaines.'],
            ['Rouge a levres mat', 'Produits de beaute', 3500, null, 40, true, true, false, null, 'Tenue longue duree, fini velours.'],
            ['Palette fards a paupieres', 'Produits de beaute', 6500, null, 12, true, true, false, null, '12 teintes mates et irisees.'],
            ['Huile de coco pure 250 ml', 'Produits de beaute', 3000, null, 25, true, true, false, null, 'Pour la peau et les cheveux.'],
            ['Savon noir africain', 'Produits de beaute', 1500, null, 3, true, true, false, null, 'Savon traditionnel exfoliant.'],
            ['Coffret soin visage de Noel', 'Produits de beaute', 15000, 18000, 10, true, true, true, 'Noel', 'Nettoyant, serum et creme dans un coffret cadeau.'],

            ['Robe pagne wax', 'Habits', 18000, null, 8, true, true, true, null, 'Coupe evasee, tissu wax 100 % coton.'],
            ['Chemise homme wax', 'Habits', 12000, null, 15, true, true, false, null, 'Manches courtes, coupe ajustee.'],
            ['T-shirt coton unisexe', 'Habits', 5000, null, 50, true, true, false, null, 'Coton bio, plusieurs coloris.'],
            // Stock a zero : illustre l'etat "en rupture" cote client.
            ['Jean slim femme', 'Habits', 9500, null, 0, true, true, false, null, 'Taille haute, toile stretch.'],
            ['Boubou brode homme', 'Habits', 25000, null, 6, true, true, true, 'Aid el-Fitr', 'Broderies main, ideal pour la fete.'],
            ['Abaya elegante', 'Habits', 20000, null, 9, true, true, false, 'Aid el-Fitr', 'Fluide et couvrante, finitions perlees.'],

            ['Eau de parfum florale 50 ml', 'Produits de beaute', 15000, null, 20, true, true, true, null, 'Notes de rose, jasmin et musc blanc.'],
            ['Eau de toilette boisee homme 100 ml', 'Produits de beaute', 18000, 21000, 11, true, true, false, null, 'Cedre, vetiver et poivre noir.'],
            ['Brume parfumee corps 250 ml', 'Produits de beaute', 6000, null, 35, true, true, false, null, 'Vanille et fleur de coton.'],
            ['Musc blanc en huile 12 ml', 'Produits de beaute', 3500, null, 0, false, true, false, null, 'Momentanement retire de la vente.'],
            ['Encens oud bakhour', 'Produits de beaute', 5000, null, 22, true, true, false, null, 'Bois d\'oud, pour parfumer la maison.'],
            ['Coffret parfum duo Saint-Valentin', 'Produits de beaute', 30000, 36000, 7, true, true, true, 'Saint-Valentin', 'Un parfum pour elle, un pour lui.'],

            ['Collier plaque or', 'Bijoux', 9000, null, 18, true, true, true, null, 'Maille fine, pendentif coeur.'],
            ['Boucles d\'oreilles creoles', 'Bijoux', 4500, null, 30, true, true, false, null, 'Acier inoxydable dore, hypoallergenique.'],
            ['Bracelet perles', 'Bijoux', 3000, null, 4, true, true, false, null, 'Perles naturelles, ajustable.'],
            ['Montre femme doree', 'Bijoux', 20000, 24000, 6, true, true, true, null, 'Bracelet maille milanaise.'],
            ['Bague argent 925', 'Bijoux', 12000, null, 10, true, true, false, null, 'Solitaire oxyde de zirconium.'],
            ['Parure de mariage', 'Bijoux', 45000, null, 2, true, false, false, null, 'Fiche en preparation, pas encore publiee.'],
        ];

        $created = [];

        foreach ($products as [$name, $categoryName, $price, $compareAt, $stock, $available, $published, $featured, $occasion, $summary]) {
            $created[$name] = Product::updateOrCreate(
                ['slug' => str($name)->slug()->value()],
                [
                    'category_id' => $categories[$categoryName]->id,
                    'occasion_id' => $occasion ? $occasions[$occasion]->id : null,
                    'name' => $name,
                    'sku' => strtoupper(str($name)->slug()->limit(12, '')->value()).'-'.str_pad((string) (count($created) + 1), 3, '0', STR_PAD_LEFT),
                    'short_description' => $summary,
                    'description' => $summary.' '.'Article disponible en boutique et a la livraison. '
                        .'Contactez-nous sur WhatsApp pour toute question.',
                    'price' => $price,
                    'compare_at_price' => $compareAt,
                    'stock' => $stock,
                    'is_available' => $available,
                    'is_published' => $published,
                    'is_featured' => $featured,
                    'published_at' => $published ? now()->subDays(count($created)) : null,
                ]
            );
        }

        $packs = [
            [
                'name' => 'Pack Beaute Essentiel',
                'description' => 'Creme karite, huile de coco et savon noir : la routine complete.',
                'price' => 8000,
                'featured' => true,
                'occasion' => null,
                'items' => [
                    'Creme hydratante karite 200 ml' => 1,
                    'Huile de coco pure 250 ml' => 1,
                    'Savon noir africain' => 1,
                ],
            ],
            [
                'name' => 'Coffret Saint-Valentin',
                'description' => 'Parfum floral, collier plaque or et rouge a levres : le cadeau ideal.',
                'price' => 25000,
                'featured' => true,
                'occasion' => 'Saint-Valentin',
                'items' => [
                    'Eau de parfum florale 50 ml' => 1,
                    'Collier plaque or' => 1,
                    'Rouge a levres mat' => 1,
                ],
            ],
            [
                'name' => 'Coffret Noel pour elle',
                'description' => 'Brume parfumee, creoles et creme karite sous le sapin.',
                'price' => 13000,
                'featured' => true,
                'occasion' => 'Noel',
                'items' => [
                    'Brume parfumee corps 250 ml' => 1,
                    'Boucles d\'oreilles creoles' => 1,
                    'Creme hydratante karite 200 ml' => 1,
                ],
            ],
            [
                'name' => 'Pack Fete de l\'Aid',
                'description' => 'Boubou brode et encens oud pour celebrer la fete.',
                // Prix laisse vide : facture a la somme des produits.
                'price' => null,
                'featured' => false,
                'occasion' => 'Aid el-Fitr',
                'items' => [
                    'Boubou brode homme' => 1,
                    'Encens oud bakhour' => 2,
                ],
            ],
        ];

        $createdPacks = [];

        foreach ($packs as $index => $definition) {
            $pack = Pack::updateOrCreate(
                ['slug' => str($definition['name'])->slug()->value()],
                [
                    'occasion_id' => $definition['occasion'] ? $occasions[$definition['occasion']]->id : null,
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'price' => $definition['price'],
                    'is_published' => true,
                    'is_featured' => $definition['featured'],
                    'position' => $index + 1,
                ]
            );

            $pack->items()->delete();

            foreach ($definition['items'] as $productName => $quantity) {
                $pack->items()->create([
                    'product_id' => $created[$productName]->id,
                    'quantity' => $quantity,
                ]);
            }

            $createdPacks[$definition['name']] = $pack;
        }

        $this->seedOffers($occasions, $created, $createdPacks);
    }

    /**
     * Prochaine occurrence de chaque periode. Les fetes musulmanes suivent le
     * calendrier lunaire : leurs dates sont indicatives et a ajuster chaque
     * annee depuis le dashboard.
     *
     * @return array<string, Occasion>
     */
    private function seedOccasions(): array
    {
        $definitions = [
            ['Noel', 'Cadeaux de fin d\'annee pour toute la famille.', $this->nextYearly(12, 1, 12, 31)],
            ['Saint-Valentin', 'Faites plaisir a votre moitie.', $this->nextYearly(2, 1, 2, 14)],
            ['Aid el-Fitr', 'Tenues et parfums pour la fin du Ramadan.', ['2027-02-27', '2027-03-12']],
            ['Tabaski', 'Aid el-Kebir : habillez-vous pour la fete.', ['2027-05-06', '2027-05-18']],
        ];

        $occasions = [];

        foreach ($definitions as $position => [$name, $description, [$start, $end]]) {
            $occasions[$name] = Occasion::updateOrCreate(
                ['slug' => str($name)->slug()->value()],
                [
                    'name' => $name,
                    'description' => $description,
                    'starts_on' => $start,
                    'ends_on' => $end,
                    'is_active' => true,
                    'position' => $position + 1,
                ]
            );
        }

        return $occasions;
    }

    /**
     * @param  array<string, Occasion>  $occasions
     * @param  array<string, Product>  $products
     * @param  array<string, Pack>  $packs
     */
    private function seedOffers(array $occasions, array $products, array $packs): void
    {
        $offers = [
            [
                'name' => 'Offre de lancement',
                'description' => 'Pour feter l\'ouverture de la boutique.',
                'discount_percent' => 10,
                'occasion' => null,
                // En cours des l'installation, pour voir les prix barres.
                'starts_on' => now()->toDateString(),
                'ends_on' => now()->addDays(30)->toDateString(),
                'products' => ['Collier plaque or', 'Bracelet perles', 'Robe pagne wax'],
                'packs' => ['Pack Beaute Essentiel'],
            ],
            [
                'name' => 'Magie de Noel',
                'description' => '15 % sur une selection de parfums.',
                'discount_percent' => 15,
                'occasion' => 'Noel',
                'products' => ['Eau de parfum florale 50 ml', 'Eau de toilette boisee homme 100 ml', 'Brume parfumee corps 250 ml'],
                'packs' => [],
            ],
            [
                'name' => 'Love Week',
                'description' => '20 % sur les bijoux pour la Saint-Valentin.',
                'discount_percent' => 20,
                'occasion' => 'Saint-Valentin',
                'products' => ['Collier plaque or', 'Bague argent 925', 'Montre femme doree'],
                'packs' => [],
            ],
            [
                'name' => 'Aid Moubarak',
                'description' => '10 % sur les habits de fete.',
                'discount_percent' => 10,
                'occasion' => 'Aid el-Fitr',
                'products' => ['Robe pagne wax', 'Chemise homme wax'],
                'packs' => [],
            ],
        ];

        foreach ($offers as $definition) {
            $offer = Offer::updateOrCreate(
                ['name' => $definition['name']],
                [
                    'description' => $definition['description'],
                    'discount_percent' => $definition['discount_percent'],
                    'occasion_id' => $definition['occasion'] ? $occasions[$definition['occasion']]->id : null,
                    'starts_on' => $definition['starts_on'] ?? null,
                    'ends_on' => $definition['ends_on'] ?? null,
                    'is_active' => true,
                ]
            );

            $offer->syncTargets(
                collect($definition['products'])->map(fn ($name) => $products[$name]->id)->all(),
                collect($definition['packs'])->map(fn ($name) => $packs[$name]->id)->all(),
            );
        }
    }

    /**
     * Bornes de la prochaine occurrence (ou de l'occurrence en cours) d'une
     * periode annuelle a dates fixes.
     *
     * @return array{0: string, 1: string}
     */
    private function nextYearly(int $startMonth, int $startDay, int $endMonth, int $endDay): array
    {
        $year = now()->year;
        $end = Carbon::create($year, $endMonth, $endDay);

        if ($end->lt(now()->startOfDay())) {
            $year++;
        }

        return [
            Carbon::create($year, $startMonth, $startDay)->toDateString(),
            Carbon::create($year, $endMonth, $endDay)->toDateString(),
        ];
    }
}
