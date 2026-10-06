<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Produits illustres par les photos du dossier img/ : les photos "beaute"
 * vont dans la categorie Produits de beaute, les pyjamas dans Habits.
 *
 * Les photos sont copiees sur le disque public (products/) comme le ferait
 * un envoi depuis le dashboard. Relancer le seeder remplace les photos.
 */
class PhotoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Produits de beaute' => Category::firstOrCreate(
                ['slug' => 'produits-de-beaute'],
                ['name' => 'Produits de beaute', 'position' => 1, 'is_active' => true],
            ),
            'Habits' => Category::firstOrCreate(
                ['slug' => 'habits'],
                ['name' => 'Habits', 'position' => 2, 'is_active' => true],
            ),
        ];

        $products = [
            // [nom, categorie, prix, stock, vedette, resume, photos]
            ['Coffret Yves Rocher Noix de Coco', 'Produits de beaute', 22000, 8, true, 'Lait corps, bain douche et gommage creme parfumes a la noix de coco.', ['beaute.jpeg']],
            ['Coffret Yves Rocher Mandarine Aiguilles de Pin', 'Produits de beaute', 25000, 6, true, 'Gommage corps, gel douche, savons et duo baume levres & creme mains.', ['beaute1.jpeg']],
            ['Gamme Yves Rocher Fleur des Pres', 'Produits de beaute', 6500, 15, false, 'Bain douche, gommage, lait corps, brume et creme mains a la fleur des pres.', ['beaute 2.jpeg']],
            ['Gloss Kiko Milano 3D Hydra', 'Produits de beaute', 7500, 25, true, 'Gloss effet volume, nombreuses teintes gourmandes.', ['beaute3.jpeg', 'beaute 6.jpeg']],
            ['Huile a levres Gisou au miel', 'Produits de beaute', 18000, 12, true, 'Honey Infused Lip Oil 8 ml, nourrit et fait briller les levres.', ['beaute4.jpeg', 'beaute 5.jpeg']],
            ['Coffret mini baumes Summer Fridays', 'Produits de beaute', 16000, 10, false, 'Quatre mini Lip Butter Balm de 5 g.', ['beaute 8.jpeg']],
            ['Baume a levres Summer Fridays', 'Produits de beaute', 14000, 20, false, 'Lip Butter Balm 15 g, hydratation intense.', ['beaute 9.jpeg']],
            ['Gloss Fenty Beauty Gloss Bomb', 'Produits de beaute', 15000, 14, false, 'Gloss brillant et confortable, teintes nacrees.', ['beaute 10.jpeg']],
            ['Gloss Rare Beauty', 'Produits de beaute', 16000, 12, false, 'Gloss lumineux a reflets irises.', ['beaute 11.jpeg']],
            ['Baume teinte Rhode Peptide Lip Tint', 'Produits de beaute', 17000, 16, true, 'Baume a levres teinte aux peptides, 10 ml.', ['beaute 12.jpeg']],
            ['Parfums Adopt', 'Produits de beaute', 6000, 40, false, 'Large choix d\'eaux de parfum Adopt, flacons 30 ml.', ['beaute 13.jpeg']],
            ['Coffret Rituals The Ritual of Sakura', 'Produits de beaute', 20000, 7, false, 'Mousse de douche, huile de douche, creme corps et brume.', ['beaute 14.jpeg']],

            ['Pyjama coton rayures et fleurs', 'Habits', 15000, 12, false, 'Pyjama chemise et pantalon en coton, motifs bleus.', ['pyjama.jpeg']],
            ['Pyjama satin Not Just Pajama', 'Habits', 30000, 6, true, 'Pyjama en satin livre dans sa boite cadeau.', ['pyjamas.jpeg']],
            ['Pyjama satin manches longues', 'Habits', 14000, 20, true, 'Chemise et pantalon satines, plusieurs coloris.', ['pyjamas 1.jpeg']],
            ['Pyjama Victoria\'s Secret manches longues', 'Habits', 25000, 9, false, 'Pyjama doux et fluide a passepoil contraste.', ['pyjamas 2.jpeg']],
            ['Pyjama short satin rayures et noeuds', 'Habits', 12000, 15, false, 'Ensemble chemise et short, ideal pour une soiree entre filles.', ['pyjamas 3.jpeg']],
            ['Pyjama short satin Victoria\'s Secret', 'Habits', 22000, 10, false, 'Chemise manches courtes et short en satin.', ['pyjamas 4.jpeg']],
            ['Pyjama short coton imprime', 'Habits', 9000, 25, false, 'Ensemble short en coton, imprimes oursons, renards, coeurs...', ['pyjamas 5.jpeg']],
            ['Pyjama satin brode personnalise', 'Habits', 20000, 8, true, 'Pyjama en satin brode au prenom de votre choix.', ['pyjamas 6.jpeg']],
        ];

        foreach ($products as $index => [$name, $categoryName, $price, $stock, $featured, $summary, $photos]) {
            $slug = str($name)->slug()->value();

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categories[$categoryName]->id,
                    'name' => $name,
                    'sku' => 'IMG-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'short_description' => $summary,
                    'description' => $summary.' Article disponible en boutique et a la livraison. '
                        .'Contactez-nous sur WhatsApp pour toute question.',
                    'price' => $price,
                    'stock' => $stock,
                    'is_available' => true,
                    'is_published' => true,
                    'is_featured' => $featured,
                ]
            );

            // Delete un par un pour que le modele supprime aussi les anciens fichiers.
            $product->images()->get()->each->delete();

            foreach ($photos as $position => $photo) {
                $path = 'products/'.$slug.'-'.($position + 1).'.'.pathinfo($photo, PATHINFO_EXTENSION);

                Storage::disk('public')->put($path, File::get(base_path('img/'.$photo)));

                $product->images()->create([
                    'path' => $path,
                    'alt' => $name,
                    'position' => $position,
                    'is_primary' => $position === 0,
                ]);
            }
        }
    }
}
