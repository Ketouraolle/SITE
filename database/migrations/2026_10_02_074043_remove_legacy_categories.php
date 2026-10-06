<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire les anciennes categories Epicerie, Boissons et Maison.
 *
 * Leurs produits sont supprimes logiquement (soft delete) : les commandes
 * passees conservent leur reference et restent lisibles dans le dashboard.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $slugs = ['epicerie', 'boissons', 'maison'];

    public function up(): void
    {
        $categoryIds = DB::table('categories')->whereIn('slug', $this->slugs)->pluck('id');

        if ($categoryIds->isEmpty()) {
            return;
        }

        DB::table('products')
            ->whereIn('category_id', $categoryIds)
            ->whereNull('deleted_at')
            ->update([
                'is_published' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('categories')->whereIn('id', $categoryIds)->delete();
    }

    public function down(): void
    {
        // Suppression definitive : rien a restaurer.
    }
};
