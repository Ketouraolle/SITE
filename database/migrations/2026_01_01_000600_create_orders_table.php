<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Numero unique lisible par le client, ex. CMD-00125.
            $table->string('number')->nullable()->unique();

            // Jeton aleatoire : seule clef permettant de consulter la page de
            // confirmation, pour empecher l'enumeration des commandes.
            $table->string('public_token', 64)->unique();

            $table->string('customer_name');
            $table->string('customer_phone', 40);
            $table->string('customer_address');
            $table->text('customer_note')->nullable();

            $table->string('status', 20)->default('pending');
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 10)->default('FCFA');
            $table->unsignedInteger('items_count')->default(0);

            // Message WhatsApp genere a la validation, conserve pour pouvoir
            // le renvoyer depuis le dashboard.
            $table->text('whatsapp_message')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('confirmed_at')->nullable();

            // Renseigne quand le stock reserve par la commande a ete rendu
            // (annulation). Evite un double credit si le statut rebascule.
            $table->timestamp('stock_released_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
