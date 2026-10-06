<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offre promotionnelle : une remise en pourcentage sur une selection
        // de produits et de packs. Rattachee a une periode, elle en suit les
        // dates ; sinon elle utilise ses propres dates.
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occasion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('discount_percent');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('offer_product', function (Blueprint $table) {
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['offer_id', 'product_id']);
        });

        Schema::create('offer_pack', function (Blueprint $table) {
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->primary(['offer_id', 'pack_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_pack');
        Schema::dropIfExists('offer_product');
        Schema::dropIfExists('offers');
    }
};
