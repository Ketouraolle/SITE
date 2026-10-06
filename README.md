# Boutique en ligne — Laravel + Vue + WhatsApp

Application de vente de produits, **mobile-first**, où le client commande **sans
créer de compte** et finalise sa commande sur **WhatsApp**. Seul
l'administrateur possède un compte.

- **Back-end** : Laravel 13, PHP 8.4
- **Front-end** : Vue 3 + Inertia 2 + Tailwind CSS 4 (Vite)
- **Base de données** : PostgreSQL (MySQL également pris en charge)

---

## 1. Installation

```bash
composer install
npm install
```

### Base de données

Créez une base puis renseignez `.env` :

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=site_boutique
DB_USERNAME=postgres
DB_PASSWORD=root
```

> Pour MySQL : `DB_CONNECTION=mysql` et `DB_PORT=3306`. Aucune autre
> modification n'est nécessaire — les recherches basculent automatiquement
> entre `ILIKE` (PostgreSQL) et `LIKE` (MySQL / SQLite).

Puis :

```bash
php artisan migrate --seed   # schéma + catalogue de démonstration
php artisan storage:link     # rend les images téléversées accessibles
npm run build                # ou `npm run dev` pendant le développement
```

Le seeder crée un compte administrateur à partir de `.env` :

```dotenv
ADMIN_EMAIL="admin@maboutique.cm"
ADMIN_PASSWORD="password"
```

**Changez ce mot de passe avant toute mise en ligne.**

---

## 2. Configuration de la boutique

Tout se règle dans `.env` (voir `config/shop.php`) :

```dotenv
APP_NAME="Ma Boutique"          # nom affiché partout sur le site

# Numéro WhatsApp du vendeur, format international SANS "+" ni espaces.
SHOP_WHATSAPP_NUMBER=237699000000

SHOP_CURRENCY="FCFA"
SHOP_PHONE="+237 6 99 00 00 00"
SHOP_EMAIL="contact@maboutique.cm"
SHOP_ADDRESS="Yaounde, Cameroun"

SHOP_LOW_STOCK_THRESHOLD=5      # seuil d'alerte « bientôt épuisé »
SHOP_CUSTOM_PACK_MIN=2          # produits minimum dans un pack client
SHOP_CUSTOM_PACK_MAX=20
```

`SHOP_WHATSAPP_NUMBER` est **la seule valeur indispensable** pour que la
redirection WhatsApp fonctionne. Si elle est absente, les commandes sont
quand même enregistrées et l'interface le signale clairement au client.

### Passer plus tard à la WhatsApp Business API

`App\Services\WhatsAppService` isole déjà la génération du message de son
envoi. Le driver actuel est `click_to_chat` (lien `wa.me`) ; pour brancher
l'API officielle, implémentez un driver `business_api` qui consomme le même
`messageFor(Order $order)` et basculez `SHOP_WHATSAPP_DRIVER`.

---

## 3. Comptes administrateurs

Il n'existe **aucune route d'inscription** : la table `users` est réservée à
l'administration. Pour créer un compte :

```bash
php artisan shop:create-admin
```

Connexion sur `/login`, dashboard sur `/admin`.

---

## 4. Parcours client

| Page | URL | Rôle |
|---|---|---|
| Accueil | `/` | mise en avant, rayons, packs, « comment ça marche » |
| Catalogue | `/catalogue` | recherche, filtres (catégorie, disponibilité, prix), tri, pagination |
| Fiche produit | `/produit/{slug}` | galerie, état du stock, ajout au panier |
| Packs | `/packs`, `/packs/{slug}` | packs prédéfinis par l'administrateur |
| Composer mon pack | `/mon-pack` | le client assemble ses propres produits, total en direct |
| Panier | `/panier` | quantités, suppression, alertes de stock, total |
| Commande | `/commande` | nom, téléphone, adresse — sans compte |
| Confirmation | `/commande/{numero}/{jeton}` | récapitulatif + redirection WhatsApp |

Les produits **en rupture** ou **indisponibles** restent visibles et sont
signalés explicitement, mais ne peuvent pas être commandés.

La page de confirmation n'est accessible qu'avec son jeton aléatoire : un
numéro de commande seul ne permet pas de consulter la commande d'autrui.

---

## 5. Dashboard administrateur

| Section | URL | Rôle |
|---|---|---|
| Tableau de bord | `/admin` | commandes à traiter, chiffre d'affaires, alertes stock, graphique 14 jours |
| Commandes | `/admin/commandes` | recherche, filtre par statut, détail, changement de statut |
| Produits | `/admin/produits` | CRUD, images multiples, publication, disponibilité |
| Catégories | `/admin/categories` | CRUD, ordre d'affichage, activation |
| Packs | `/admin/packs` | composition, prix fixe ou somme des produits, publication |
| Stocks | `/admin/stocks` | ajustement unitaire ou en lot, filtres rupture / stock faible |
| Mon compte | `/admin/profil` | nom, e-mail, mot de passe |

### Statuts de commande

`Nouvelle` → `Confirmée` → `En préparation` → `Livrée`, plus `Annulée`.

Passer une commande en **Annulée** remet automatiquement les articles en
stock ; la repasser dans un autre statut les reprend. Le double crédit est
impossible (`orders.stock_released_at`).

---

## 6. Cycle de commande

1. Le client valide son panier.
2. Laravel ouvre une transaction et **verrouille** (`lockForUpdate`) les lignes
   produits concernées, packs inclus.
3. Publication, disponibilité et stock sont revérifiés ; en cas de problème la
   commande est refusée avec un message précis et aucun stock n'est touché.
4. La commande est enregistrée, le stock décrémenté, un numéro unique généré
   (`CMD-00125`, dérivé de la clé primaire — pas de compteur concurrent).
5. Le message WhatsApp est généré et conservé en base.
6. Le client est redirigé vers WhatsApp avec le message prérempli.

Exemple de message généré :

```
Bonjour, je viens de passer la commande #CMD-00125.
Produits : Riz parfume 5 kg x 2, Pack Petit Dejeuner (Cafe moulu 250 g x1, ...) x 1
Total : 25 000 FCFA
Nom : Jean Mbarga
Telephone : +237 699 00 00 00
Adresse : Yaounde, Bastos
```

---

## 7. Base de données

| Table | Contenu |
|---|---|
| `users` | administrateurs uniquement |
| `categories` | rayons du catalogue |
| `products` | prix, stock, `is_available`, `is_published`, suppression logique |
| `product_images` | images multiples, image principale ; le fichier est supprimé avec la ligne |
| `packs` | packs prédéfinis, prix fixe optionnel |
| `pack_items` | composition d'un pack (produit + quantité) |
| `orders` | client, statut, total, numéro, jeton public, message WhatsApp |
| `order_items` | instantané des lignes (`product` / `pack` / `custom_pack`) + composition JSON |

L'état affiché au client (*en stock*, *bientôt épuisé*, *en rupture*,
*indisponible*) n'est **jamais stocké** : il est dérivé du couple
(`is_available`, `stock`) par `App\Enums\StockState`.

Les lignes de commande conservent un instantané du nom et du prix : supprimer
un produit ne dénature pas les commandes passées.

---

## 8. Panier

Le panier vit **en session** (`App\Services\CartService`) : le client n'a pas
de compte. Seules des références y sont stockées (type, identifiants,
quantités) ; prix, noms et disponibilités sont **relus en base à chaque
affichage**. Un panier ne peut donc pas figer un ancien prix, et une ligne
dont le produit a été dépublié disparaît automatiquement.

---

## 9. Tests

```bash
php artisan test
```

106 tests couvrent le catalogue et ses filtres, le panier (produits, packs,
packs personnalisés), la validation du stock à la commande, la génération du
numéro et du message WhatsApp, la protection de la page de confirmation, et
l'ensemble du dashboard (CRUD, publication, statuts, remise en stock,
téléversement d'images).

Style de code :

```bash
vendor/bin/pint
```

---

## 10. Notes de développement

### Lancer l'application

Le projet est prévu pour **Laravel Herd** (`http://site.test`). En
développement, lancez Vite en parallèle :

```bash
npm run dev
```

### `php artisan serve` et le téléversement d'images sous Windows

Avec `php artisan serve`, le serveur enfant est démarré avec un
environnement filtré : `TMP` / `TEMP` n'étant pas transmis, PHP ne trouve pas
de dossier temporaire et **tout téléversement de fichier échoue** avec
« Le fichier du champ image n'a pu être téléversé » (`UPLOAD_ERR_NO_TMP_DIR`).

Ce n'est pas un défaut de l'application. Trois solutions :

1. servir le site via **Herd** (recommandé, c'est le cas par défaut ici) ;
2. renseigner `upload_tmp_dir` dans votre `php.ini` ;
3. ajouter `E` à `variables_order` dans `php.ini` (`variables_order = "EGPCS"`).

Le reste de l'application fonctionne normalement avec `php artisan serve`.
