# EauRelais — première version fonctionnelle

**Signaler une panne, accélérer le retour de l’eau.**

Application Laravel + Inertia.js + Vue 3 pour coordonner les réparations des points d’eau. Interface française, pensée pour les téléphones, avec signalement sans compte et suivi par code secret.

## Ce que contient cette version

- Accueil public, signalement avec photo et position facultatives, suivi privé par référence et code secret.
- Répertoire des points d’eau, gestion des fiches, état déclaré et dates d’entretien.
- Connexion et rôles usager/école, relais, gestionnaire, technicien et administrateur.
- Vérification, priorisation, affectation, plusieurs visites par incident, diagnostic, pièces, coûts en FCFA, réparation et confirmation.
- Non-retenu, doublon relié à un incident principal, réouverture et historique horodaté.
- Entretiens préventifs, comptes rendus et rappels dans l’application.
- Tableau de bord filtrable, délais documentés, récurrences et export CSV sans coordonnées personnelles.
- Administration des comptes et des rôles, référencement des techniciens, communes, localités et journal d’audit.
- PWA, brouillon explicite sur l’appareil, file IndexedDB, reprise des envois et protection contre les doublons.

## 1. Prérequis

- PHP 8.2 ou 8.3, avec PDO MySQL, mbstring, XML/DOM, cURL, fileinfo, tokenizer, ctype et OpenSSL. SQLite est utile pour les tests.
- Composer 2.
- Node.js 20.19+ ou 22+ et npm.
- MySQL 8+ ou MariaDB 10.11+.

Les versions exactes sont verrouillées dans `composer.lock` et `package-lock.json`. Socle vérifié : Laravel 12.70.0, Inertia Laravel 2.0.28, Vue 3, Vite 6.4.4, Tailwind 3.4, PHP 8.3.6 et MariaDB 10.11.14. Le choix de Laravel 12 garde la compatibilité PHP 8.2/8.3, notamment avec XAMPP.

## 2. Installation dans VS Code, sous Windows / XAMPP

1. Décompresser le dossier et l’ouvrir dans VS Code.
2. Démarrer MySQL depuis XAMPP. Vérifier que PHP et Composer sont accessibles dans le terminal (`php -v`, `composer --version`).
3. Dans phpMyAdmin, créer une base nommée `eaurelais` avec le classement `utf8mb4_unicode_ci`.
4. Dans le terminal PowerShell du dossier du projet :

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

5. Adapter `.env` à votre installation :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eaurelais
DB_USERNAME=root
DB_PASSWORD=
```

Le compte root sans mot de passe n’est qu’un exemple de configuration XAMPP locale. Utiliser un utilisateur dédié pour un hébergement.

6. Créer les tables et lancer :

```powershell
php artisan migrate
php artisan eaurelais:admin votre.adresse@example.com "Votre nom"
php artisan serve
```

La commande administrateur demande le mot de passe de façon masquée (12 caractères minimum). Ouvrir **http://localhost:8000**.

Le dossier `public/build` contient déjà le frontend compilé. Vous pouvez utiliser l’application sans lancer Vite. Pour modifier l’interface :

```powershell
npm ci
npm run dev
```

Garder `php artisan serve` ouvert dans un second terminal. Après modification, terminer Vite puis exécuter `npm run build` pour régénérer les fichiers et la coquille hors connexion. Ne pas laisser de fichier `public/hot` lors du déploiement.

Sous Linux/macOS, remplacer `Copy-Item .env.example .env` par `cp .env.example .env`.

## 3. Activer les données fictives de présentation

Sur une installation locale uniquement, ajouter dans `.env` :

```dotenv
APP_ENV=local
DEMO_PASSWORD=VotreMotDePasseDeDemoAChoisir
```

Choisir soi-même un mot de passe d’au moins 12 caractères, puis lancer :

```bash
php artisan db:seed
```

Comptes fictifs (mot de passe = votre valeur `DEMO_PASSWORD`) :

| Rôle | Adresse |
|---|---|
| Administrateur | admin@eaurelais.test |
| Gestionnaire | gestionnaire@eaurelais.test |
| Relais | relais@eaurelais.test |
| Technicienne | technicien@eaurelais.test |
| Second technicien | technicien2@eaurelais.test |
| Responsable d’école | usager@eaurelais.test |

Le bandeau indique explicitement les données fictives. Les incidents préchargés sont consultables avec les comptes professionnels. Leurs secrets de suivi ne sont pas publiés : créer un nouveau signalement pour tester le suivi public.

Ne pas charger ces comptes sur une base destinée à la production. Le seeder refuse de fonctionner en environnement production. Changer `DEMO_PASSWORD` après un premier chargement ne modifie pas les comptes déjà créés : utiliser l’administration pour les actualiser.

## 4. Premier démarrage sans démonstration

1. Se connecter avec l’administrateur créé en ligne de commande.
2. Dans Administration, créer une commune puis ses localités.
3. Créer les comptes du gestionnaire, des relais et des techniciens ; renseigner la commune.
4. Référencer les techniciens après vérification de leurs compétences.
5. Ajouter les points d’eau et leur état déclaré.
6. Ouvrir le formulaire public et effectuer le premier signalement.

Un gestionnaire ou un relais agit sur sa commune. Un technicien accède uniquement aux incidents auxquels il a été affecté. Dans cette version, chaque compte dispose d’un rôle et d’une commune principale ; le référencement depuis l’administration associe le technicien à cette commune.

## 5. Utilisation hors connexion

Le mode hors connexion fonctionne sur **HTTPS ou localhost**, après une première ouverture connectée du formulaire et la mise en cache des ressources.

- « Enregistrer un brouillon » conserve la saisie sans l’envoyer.
- « Enregistrer et envoyer » conserve d’abord une demande dans IndexedDB.
- Le serveur doit confirmer la réception avant l’affichage du reçu.
- La synchronisation se relance lorsque le réseau revient et que l’application est ouverte ; un bouton permet de réessayer.
- Après réception, les données personnelles et la photo sont supprimées de la file locale. Le reçu ne conserve que la référence, le code et la date, pendant 7 jours maximum.
- Télécharger le reçu avant de l’effacer d’un appareil partagé. Les brouillons et demandes non envoyées peuvent être supprimés explicitement.
- Les pages professionnelles, les photos privées et les réponses de suivi ne sont pas mises en cache par le service worker.

La synchronisation n’est pas garantie si l’application est fermée. La carte géographique, les capteurs, les analyses sanitaires et les notifications SMS/WhatsApp/e-mail ne sont pas intégrés à cette version.

## 6. Tests

```bash
composer test
npm ci
npm run test:offline
npm run build
```

`composer test` lance PHPUnit sur une base SQLite en mémoire, sans toucher à la base MySQL configurée dans `.env`. Pour tester MySQL/MariaDB, utiliser une base de test distincte et des variables d’environnement de test ; ne jamais pointer les tests vers une base avec des données réelles.

Le bilan détaillé des vérifications et les limites sont dans `docs/VALIDATION.md`. Le scénario de présentation est dans `docs/SCENARIO_PILOTE.md`.

## 7. Hébergement PHP + MySQL

Cette application nécessite un serveur PHP et MySQL/MariaDB. Elle ne s’exécute pas sur GitHub Pages ou sur un hébergement de fichiers statiques. Le backend Laravel ne correspond pas au runtime Workers du service Sites : aucun faux remplacement du backend n’a été publié.

- Installer les dépendances PHP avec `composer install --no-dev --optimize-autoloader`.
- Utiliser une base et un utilisateur dédiés et renseigner les identifiants uniquement dans `.env`.
- Utiliser `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://votre-domaine`, `SESSION_SECURE_COOKIE=true` et une clé générée sur le serveur.
- Faire pointer la racine web vers **public/**, jamais vers la racine du projet.
- Rendre `storage/` et `bootstrap/cache/` accessibles en écriture au processus PHP.
- Compiler avec `npm ci && npm run build`, puis lancer `php artisan migrate --force` et `php artisan optimize`.
- Sauvegarder la base et les fichiers privés. Conserver la clé d’application.

Rappels d’entretien : ajouter une tâche cron chaque minute :

```cron
* * * * * cd /chemin/eaurelais && php artisan schedule:run >> /dev/null 2>&1
```

Les notifications sont stockées en base, visibles dans l’application. Aucun worker de queue ni compte de messagerie externe n’est nécessaire pour le MVP.

## 8. Organisation

- `app/Http/Controllers` : opérations métier et validation.
- `app/Policies` : accès aux incidents.
- `app/Services/Workflow.php` : transitions et confirmation.
- `database/migrations` : schéma relationnel.
- `database/seeders` : données de présentation facultatives.
- `resources/js/Pages` : écrans Vue.
- `resources/js/lib` : stockage local, compression et synchronisation.
- `scripts/build-offline.mjs` : génération de la PWA après compilation.
- `tests` : tests serveur et file hors connexion.

**L’état fonctionnel d’un point d’eau ne vaut pas preuve de potabilité.**
