Tracktoon — README (a lire en code)

Suivez vos manhwas, manhuas et mangas : créez un compte, gérez votre bibliothèque (statut, favoris, notes), notez les œuvres, consultez la FAQ…
Stack : PHP 8.3, Apache, MySQL, Twig, Composer, Sass, Vanilla JS. Tests unitaires avec PHPUnit. Environnement local : WAMP (Windows), mais fonctionne aussi hors-WAMP.

Sommaire

Prérequis

Arborescence du projet

Configuration de l’environnement

Installation

Base de données

Lancer en local

Compilation des styles (Sass → CSS)

Tests unitaires

Qualité/Validation W3C

Conseils de prod / sécurité

Dépannage

Prérequis

PHP 8.3 (extensions : pdo_mysql, mbstring, json, ctype, openssl, curl)

Apache 2.4 (avec mod_rewrite activé)

MySQL 5.7+ / 8+

Composer 2.8+

Node.js 18+ & npm (ou sass CLI) — pour compiler le SCSS

(Windows) WAMP 3.3+ si vous préférez un stack packagé

Optionnel :

PHPUnit (installé via Composer dans vendor/)

Docker Desktop si vous souhaitez conteneuriser

Arborescence du projet
Tracktoon/
├─ config/
│  └─ autoload.php
├─ controllers/
├─ managers/
├─ models/
├─ services/
├─ templates/
│  ├─ layouts/
│  └─ pages/
├─ styles/
│  ├─ scss/
│  └─ css/           # fichiers compilés
├─ js/
├─ images/
├─ fonts/
├─ test/             # tests PHPUnit
├─ vendor/           # Composer
├─ index.php         # point d'entrée
├─ .env              # variables locales (non commité)
├─ composer.json / composer.lock
├─ package.json / package-lock.json
└─ README.md

Configuration de l’environnement

Les secrets et la configuration sensible sont chargés depuis .env via phpDotenv.

Créez un fichier .env à la racine :

APP_ENV=local
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=anthelmejarreau_Tracktoon
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Exemples d’autres variables
MAILER_DSN=smtp://user:pass@smtp.example.com:587


📝 Ne commitez jamais .env. Le dépôt contient déjà un .gitignore.

Installation

Cloner le dépôt

git clone <url-du-repo> Tracktoon
cd Tracktoon


Installer les dépendances PHP

composer install


Installer les dépendances front (pour Sass)

npm ci
# ou, si vous n’utilisez pas npm : installez la CLI "sass"


Créer le fichier .env (voir plus haut)

Base de données

Le dump d’exemple (tel que fourni dans votre dossier projet) doit être importé dans MySQL.

Créez au préalable la base anthelmejarreau_Tracktoon (ou adaptez DB_NAME dans .env).

Import via phpMyAdmin

Ouvrez phpMyAdmin → sélectionnez la base → onglet Importer → choisissez le fichier .sql → Exécuter.

Import via CLI (si vous avez mysql en PATH)

mysql -h 127.0.0.1 -u root -p
# puis dans le client, exécutez:
# USE anthelmejarreau_Tracktoon;
# SOURCE /chemin/vers/dump.sql;


Le schéma crée les tables users, books, genders, scores, library, les relations & index.
La colonne books.description peut être TEXT (recommandé pour de longues descriptions).

Lancer en local
Option A — WAMP (recommandé sur Windows)

Placez le projet là où WAMP peut le servir, ou gardez-le où il est et configurez un VirtualHost pointant sur le dossier du projet.

Activez mod_rewrite (WAMP → Apache modules).

Assurez-vous que index.php (à la racine) est accessible (ex. http://localhost/Tracktoon ou via votre vhost).

Option B — Apache “nu”

Créez un vhost (ex. tracktoon.local) qui pointe sur le dossier du projet.

Activez mod_rewrite.

Redémarrez Apache.

Option C — Serveur PHP embarqué (pour debug rapide)

⚠️ Non recommandé pour un usage réel : pas de réécriture avancée ni d’Apache.

php -S localhost:8080 -t .
# puis http://localhost:8080

Compilation des styles (Sass → CSS)

Le CSS consommé par l’app est le résultat compilé depuis styles/scss.

Via npm (scripts)

npm run build     # compilation de production (selon votre package.json)
npm run dev       # watch (si prévu)


Via CLI sass (sans npm)

sass styles/scss:styles/css --no-source-map --style=compressed


Assurez-vous que templates/layouts/base.html.twig référence bien vos fichiers CSS compilés (ex. /styles/css/main.css).

Tests unitaires

Les tests se trouvent dans test/.

Lancer PHPUnit

./vendor/bin/phpunit
# ou pour un fichier précis
./vendor/bin/phpunit test/UserControllerRegisterTest.php


Dans les tests, l’autoload.php du projet est requis depuis config/autoload.php.
Le contrôleur UserController est “surchargé” dans les tests pour capter la redirection sans faire de header() réel.

Qualité/Validation W3C

Pour valider les pages avec le validateur W3C avant mise en ligne :

Lancer l’app localement (WAMP/Apache).

Ouvrir la page, afficher le code source (Ctrl+U).

Copier ce HTML et le coller dans le validateur : https://validator.w3.org/#validate_by_input

Corriger les éventuels problèmes (attributs, rôles ARIA redondants, select[required] sans placeholder, etc.).

Conseils de prod / sécurité

Twig auto-escape activé ('autoescape' => 'html') protège contre une large partie des failles XSS lors de l’affichage.

CSRF : jetons gérés par CSRFTokenManager.

Mots de passe : hashés via password_hash() / vérifiés avec password_verify().

Entrées : validez côté serveur (emails via filter_var, regex de mot de passe, etc.).

Secrets : jamais en clair dans le code/depot ; toujours via variables d’environnement.

phpMyAdmin : évitez de l’exposer en public.

Dépannage

Page blanche / 500 :

Activez display_errors en local, vérifiez APP_ENV/APP_DEBUG, inspectez error_log Apache.

Vérifiez que vendor/ est présent (faites composer install).

Connexion DB échoue :

Vérifiez les variables .env (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT).

Testez la connexion MySQL avec un client externe.

CSS non appliqué :

Compilez Sass → CSS.

Vérifiez les chemins <link href="/styles/css/main.css">.

Actions AJAX (notation / auto-save) non actives :

Ouvrez la console du navigateur (F12) → onglet Network, regardez les requêtes POST.

Confirmez la présence des bons data-* dans le HTML et que js/app.js est bien inclus.

(Bonus) Démarrage rapide avec Docker

Optionnel — si vous avez Docker Desktop et un Dockerfile.

docker build -t tracktoon:latest .
docker run -p 8080:80 --env-file .env tracktoon:latest
# http://localhost:8080


La DB peut rester externe (MySQL local/WAMP ou service managé). Renseignez simplement les variables DB_* dans .env ou via --env.