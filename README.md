# Martelia - Boutique en ligne

Boutique développée avec Laravel et Livewire. Les clients commandent sans créer de compte, puis ouvrent WhatsApp pour envoyer leur commande au vendeur.

L'administration permet de gérer les produits, les catégories, les packs, les offres, les stocks et les commandes.

## Technologies

- PHP 8.4 pour l'environnement de développement validé.
- Laravel 13.
- Livewire 4 et Alpine.js.
- Tailwind CSS 4.
- Vite 8.
- SQLite en développement et pour les tests.
- MySQL ou PostgreSQL pour la production.

## Installation locale avec PowerShell

Depuis le dossier du projet :

```powershell
composer install
npm ci
```

Créer le fichier de configuration s'il n'existe pas :

```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

Générer la clé uniquement lors de l'installation initiale. Ne pas remplacer la clé d'une application existante.

### Base de données SQLite

Vérifier dans `.env` :

```dotenv
DB_CONNECTION=sqlite
```

Créer le fichier de base de données s'il n'existe pas, puis créer les tables :

```powershell
if (!(Test-Path database\database.sqlite)) { New-Item -Path database\database.sqlite -ItemType File }
php artisan migrate
```

### Catalogue de démonstration

Uniquement sur une nouvelle base locale :

```powershell
php artisan db:seed
```

Le seeder principal charge le catalogue uniquement lorsque `APP_ENV=local`.
Il ne crée aucun administrateur.

Attention : les seeders du catalogue peuvent réécrire les prix, les stocks et les images des produits concernés. Ne pas les relancer sur un catalogue déjà personnalisé.

Ne pas exécuter directement les seeders de démonstration en production.

### Administrateur

Créer le compte avec la commande interactive :

```powershell
php artisan shop:create-admin
```

Aucun compte administrateur avec un mot de passe par défaut n'est créé par le seeder principal.

- Connexion : `/login`
- Administration : `/admin`
- Aucune inscription publique.

### Images et compilation

```powershell
php artisan storage:link
npm run build
```

### Démarrage local

```powershell
php artisan serve
```

Ouvrir l'adresse indiquée dans le terminal, généralement :

http://127.0.0.1:8000

Pour développer les styles, lancer dans un second terminal :

```powershell
npm run dev
```

Sous Windows, privilégier un chemin de projet sans accents si les commandes interactives PHP rencontrent des erreurs d'encodage.

## Configuration de la boutique

Renseigner les valeurs dans `.env` :

```dotenv
APP_NAME="Martelia"

SHOP_WHATSAPP_DRIVER=click_to_chat
SHOP_WHATSAPP_NUMBER=237679603514
SHOP_CURRENCY=FCFA

SHOP_PHONE=
SHOP_EMAIL=
SHOP_ADDRESS=

SHOP_LOW_STOCK_THRESHOLD=5
SHOP_MAX_LINE_QUANTITY=99
SHOP_CUSTOM_PACK_MIN=2
SHOP_CUSTOM_PACK_MAX=20

SHOP_PENDING_ORDER_EXPIRATION_HOURS=48
```

Le numéro WhatsApp doit contenir l'indicatif international, sans signe `+` ni espaces.

Après une modification de configuration en développement :

```powershell
php artisan config:clear
```

### Fonctionnement de WhatsApp

Le site génère un lien `wa.me` avec un message prérempli.

Le client doit envoyer lui-même le message dans WhatsApp. L'ouverture du lien ne prouve pas que le message a été envoyé.

Aucune intégration automatique à la WhatsApp Business API n'est actuellement implémentée.

## Parcours client

| Page | Adresse |
|---|---|
| Accueil | `/` |
| Catalogue | `/catalogue` |
| Produit | `/produit/{slug}` |
| Packs | `/packs` |
| Détail d'un pack | `/packs/{slug}` |
| Composer un pack | `/mon-pack` |
| Panier | `/panier` |
| Commande | `/commande` |
| Confirmation | `/commande/{numero}/{jeton}` |

Le panier est conservé en session. Les prix et disponibilités sont relus côté serveur.

La confirmation d'une commande nécessite son jeton d'accès. Ne pas publier les liens de confirmation des clients.

### Visibilité des produits

Un produit doit être publié pour apparaître dans le catalogue.

S'il est associé à une période, celle-ci doit être active et en cours.

Les produits en rupture ou indisponibles peuvent rester visibles, mais ne peuvent pas être commandés. Les filtres du catalogue peuvent aussi modifier les résultats affichés.

## Commandes et stocks

Statuts disponibles :

- Nouvelle.
- Confirmée.
- En préparation.
- Livrée.
- Annulée.

À la création d'une commande :

1. Les références du panier sont relues en base.
2. Les produits concernés sont verrouillés dans une transaction.
3. Le stock et la disponibilité sont vérifiés.
4. La commande et ses lignes sont enregistrées.
5. Le stock est diminué.
6. Le message WhatsApp est préparé.

L'annulation remet les articles en stock.

La réactivation reprend le stock et est refusée si un produit nécessaire est absent ou si les quantités sont insuffisantes. En cas d'échec, la transaction annule les ajustements effectués.

Les changements de statut verrouillent la commande avant de vérifier son état.

Les tests SQLite vérifient les comportements fonctionnels. La concurrence réelle et les verrous de lignes restent à vérifier avec MySQL ou PostgreSQL.

## Expiration des commandes après 48 heures

La commande suivante annule les commandes encore au statut « Nouvelle » dont la création remonte à au moins 48 heures :

```powershell
php artisan shop:expire-pending-orders
```

Attention : cette commande modifie les commandes et remet leurs articles en stock. Son exécution manuelle agit immédiatement sur la base configurée.

Le délai est configurable :

```dotenv
SHOP_PENDING_ORDER_EXPIRATION_HOURS=48
```

Les commandes confirmées, en préparation ou livrées ne sont pas ciblées.

Le planificateur Laravel prévoit une vérification toutes les cinq minutes :

```php
Schedule::command('shop:expire-pending-orders')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

Afficher les tâches prévues :

```powershell
php artisan schedule:list
```

Cette programmation nécessite un déclencheur système pour fonctionner automatiquement.

Sur Serv00, configurer une tâche cron qui exécute chaque minute :

```text
* * * * * cd /CHEMIN/ABSOLU/DU/PROJET && /CHEMIN/ABSOLU/DE/php84 artisan schedule:run >> /CHEMIN/ABSOLU/DU/JOURNAL 2>&1
```

Les chemins ci-dessus sont des exemples à remplacer par les chemins réels du compte.

L'expiration intervient au prochain passage après le délai, normalement dans les cinq minutes suivantes si le planificateur fonctionne correctement.

## Récupération du mot de passe

Le formulaire retourne le même message pour une adresse connue ou inconnue.

Les demandes valides sont limitées à cinq tentatives par minute et par adresse IP, en complément des protections du gestionnaire de mots de passe Laravel.

En développement :

```dotenv
MAIL_MAILER=log
```

Les e-mails sont alors écrits dans les journaux Laravel et ne sont pas envoyés.

En production, configurer un service SMTP et vérifier la réception réelle d'un lien de réinitialisation.

Ne jamais publier les identifiants SMTP, les jetons de réinitialisation ou les journaux contenant ces liens.

## Vérifications

Tests :

```powershell
php artisan test
```

Dernier bilan validé pendant les corrections : 145 tests réussis, 706 assertions.

Formatage des fichiers PHP modifiés :

```powershell
php vendor/bin/pint --dirty
```

Compilation :

```powershell
npm run build
```

Audits des dépendances :

```powershell
composer audit
npm audit
```

Ces audits recherchent les vulnérabilités connues ; ils ne remplacent pas une revue de sécurité.

Ne pas utiliser `npm audit fix --force` sans examiner les changements proposés.

## Hébergement retenu : Serv00

Le déploiement est prévu sur Serv00 avec :

- PHP 8.4.
- MySQL ou PostgreSQL.
- HTTPS.
- Stockage des images.
- Une tâche cron pour le planificateur Laravel.

L'environnement réel, les extensions PHP et les limites du compte doivent être vérifiés avant l'ouverture aux clients.

### Paramètres de production

Sur le serveur, renseigner notamment :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ADRESSE-REELLE-DU-SITE

LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
```

Configurer aussi :

- Une clé d'application propre au serveur, générée lors de l'installation initiale.
- Les identifiants de la base de données.
- Le numéro WhatsApp et les coordonnées de Martelia.
- Le service SMTP et l'adresse d'expédition.
- Le cache et les sessions.
- La tâche cron.

Conserver la clé d'application et les secrets en dehors du dépôt Git.

### Préparation du déploiement

1. Installer les dépendances PHP de production avec `composer install --no-dev --optimize-autoloader`.
2. Compiler les fichiers du site avec `npm ci` puis `npm run build` dans un environnement de construction.
3. Configurer la racine web pour servir uniquement le dossier `public`.
4. Renseigner le fichier `.env` du serveur.
5. Exécuter les migrations avec `php artisan migrate --force`.
6. Créer le compte administrateur avec `php artisan shop:create-admin`.
7. Créer le lien des images avec `php artisan storage:link`.
8. Vérifier les droits d'écriture de `storage` et `bootstrap/cache`.
9. Mettre en cache la configuration avec `php artisan config:cache`.
10. Configurer le planificateur et les sauvegardes.
11. Vérifier le catalogue, les images, les commandes, WhatsApp, les e-mails et HTTPS.

Ne pas charger les données de démonstration en production.

Ne pas transférer les commandes et coordonnées de test vers la base de production.

Ne jamais exécuter `migrate:fresh` sur une base contenant des données à conserver.

### Sauvegardes et maintien du compte

Conserver des sauvegardes indépendantes :

- De la base de données.
- Des images dans `storage/app/public`.
- De la configuration privée et de la clé d'application.

Selon les conditions Serv00 consultées le 22 octobre 2026, une connexion au panneau DevilWEB ou en SSH est nécessaire au moins une fois tous les 90 jours pour éviter la suppression du compte.

Vérifier les conditions actuelles de l'hébergeur et tester la restauration des sauvegardes.

## État avant mise en production

Déjà vérifié localement :

- Tests automatisés.
- Compilation du site.
- Audits des dépendances au moment des corrections.
- Enregistrement d'une commande.
- Ouverture du bon destinataire WhatsApp.
- Ajout et affichage d'une image depuis l'administration.

À valider sur l'hébergement :

- Accès publics et administrateur en HTTPS.
- Envoi et réception des e-mails.
- Exécution réelle de la tâche cron.
- Persistance des données et des images.
- Sauvegardes et restauration.
- Gestion des accès concurrents avec la base de production.