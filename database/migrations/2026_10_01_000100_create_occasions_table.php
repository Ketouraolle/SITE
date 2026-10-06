<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Periodes commerciales : Noel, Saint-Valentin, Aid el-Fitr...
        // Sans date de debut ou de fin, la periode est ouverte de ce cote.
        Schema::create('occasions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'starts_on', 'ends_on']);
        });

        // Produit ou pack reserve a une periode : il n'est visible et
        // commandable que pendant celle-ci.
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('occasion_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        Schema::table('packs', function (Blueprint $table) {
            $table->foreignId('occasion_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('occasion_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('occasion_id');
        });

        Schema::dropIfExists('occasions');
    }
};
