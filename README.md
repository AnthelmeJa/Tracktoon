# Tracktoon

Tracktoon est une application web permettant de **suivre ses lectures de manhwas, manhuas et mangas** : bibliothèque personnelle, statut de lecture, favoris, notes, etc.

Le projet a été développé en PHP 8 (stack WAMP au départ), puis **conteneurisé avec Docker** et déployé sur **Render**, avec une base **MySQL-compatible hébergée sur TiDB Cloud**.

---

## 🌐 Fonctionnalités principales

- Page d’accueil présentant les séries mises en avant  
- Bibliothèque utilisateur :
  - statut de lecture : à lire / en cours / terminé  
  - favoris  
  - commentaires personnels  
- Système de notes (`scores`) par utilisateur et par série  
- Gestion des genres (association `books_genders`)  
- Espace d’authentification :
  - inscription  
  - connexion / déconnexion  
  - rôles : `user`, `admin`, `super_admin`  
- Pages FAQ, contact, mentions légales, etc.  
- Thème **sombre / clair** et quelques options d’accessibilité (dyslexie)  
- Envoi d’e-mails via **PHPMailer** (si variables SMTP configurées)

---

## 🧱 Stack technique

- **Langage** : PHP 8.3  
- **Serveur web** : Apache 2.4 (image Docker officielle `php:8.3-apache`)  
- **Base de données (prod)** : TiDB Cloud (compatible MySQL)  
- **Gestionnaire de dépendances** : Composer 2  
- **Templating** : Twig  
- **Styles** :
  - Sass/SCSS (`styles/scss`)
  - CSS compilé (`styles/css`)
- **Tests** : PHPUnit (`test/`)
- **Mailing** : PHPMailer  
- **Gestion de la configuration sensible** :
  - `.env` local (non versionné)
  - `phpdotenv`
- **Conteneurisation** : Docker & Docker Hub  
- **Déploiement** : Render (web service Docker)

---

## 🗂️ Architecture du projet

Arborescence principale :

```text
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
├─ .env.docker       # variables pour Docker local
├─ composer.json / composer.lock
├─ package.json / package-lock.json
└─ README.md
Rôle des dossiers
config/

autoload.php : charge Composer + modèles + managers + services + contrôleurs.

models/
Entités métier (Users, Book, Scores, Library, Gender, etc.).

managers/

AbstractManager : ouvre la connexion PDO (MySQL/TiDB) en lisant les variables d’environnement.

Managers spécifiques : UsersManager, BooksManager, etc.

services/

Router : résout les routes (?route=home, ?route=login, etc.).

CSRFTokenManager : gestion des tokens CSRF.

templates/

Layout global : layouts/base.html.twig

Pages : pages/...

styles/ : SCSS source + CSS compilé.

js/ : JavaScript (menu, thème sombre, etc.).

test/ : tests unitaires PHPUnit.

⚙️ Variables d’environnement
L’application repose sur des variables d’environnement pour la base de données et l’envoi d’e-mails.

Variables DB (communes Docker / Render / TiDB)
dotenv

APP_ENV=dev|prod
APP_DEBUG=true|false

DB_HOST=        # host TiDB ou MySQL
DB_PORT=        # port (4000 pour TiDB Serverless, 3306 pour MySQL classique)
DB_NAME=        # nom de la base (ex : test)
DB_USER=        # utilisateur DB
DB_PASSWORD=    # mot de passe DB
DB_CHARSET=utf8mb4

# Pour TiDB Cloud en TLS (Docker & Render)
DB_SSL_CA_PATH=/etc/ssl/certs/ca-certificates.crt
Dans AbstractManager, la connexion PDO est construite comme suit :

php

$host    = getenv('DB_HOST')     ?: ($_ENV['DB_HOST']     ?? '127.0.0.1');
$port    = getenv('DB_PORT')     ?: ($_ENV['DB_PORT']     ?? '3306');
$dbName  = getenv('DB_NAME')     ?: ($_ENV['DB_NAME']     ?? 'tracktoon');
$charset = getenv('DB_CHARSET')  ?: ($_ENV['DB_CHARSET']  ?? 'utf8mb4');
$user    = getenv('DB_USER')     ?: ($_ENV['DB_USER']     ?? 'root');
$pass    = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
Et des options PDO supplémentaires permettent d’activer TLS pour TiDB :

php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$sslCaPath = getenv('DB_SSL_CA_PATH') ?: ($_ENV['DB_SSL_CA_PATH'] ?? null);
if ($sslCaPath) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

$this->db = new PDO($dsn, $user, $pass, $options);
Variables mail (PHPMailer)
dotenv

MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=
MAIL_FROM_NAME=
MAIL_TO=
Si elles restent vides, les fonctionnalités d’envoi d’email peuvent être désactivées ou gérées avec des garde-fous dans le code.

💻 Installation & exécution en local (WAMP)
1. Cloner le dépôt
bash

git clone <url-du-repo>
cd Tracktoon
2. Installer les dépendances PHP
bash

composer install
3. Installer les dépendances front (optionnel, si besoin de recompiler le CSS)
bash

npm install
# puis
npm run build   # ou npm run dev selon package.json
4. Créer un fichier .env à la racine
dotenv

APP_ENV=dev
APP_DEBUG=true

DB_HOST=...
DB_PORT=3306
DB_NAME=...
DB_USER=...
DB_PASSWORD=...
DB_CHARSET=utf8mb4
5. Lancer via WAMP
Placer le projet dans le répertoire servi par WAMP (ou configurer un VirtualHost qui pointe vers ce dossier), puis accéder à :

text

http://localhost/Tracktoon
🐳 Exécution en local avec Docker (image seule + TiDB Cloud)
1. Préparer .env.docker
dotenv

APP_ENV=dev
APP_DEBUG=true

DB_HOST=<host_tidb>
DB_PORT=4000
DB_NAME=test
DB_USER=<user_tidb>
DB_PASSWORD=<password_tidb>
DB_CHARSET=utf8mb4
DB_SSL_CA_PATH=/etc/ssl/certs/ca-certificates.crt

# éventuellement les variables MAIL_*
2. Builder l’image Docker
bash

docker build -t tracktoon:latest .
3. Lancer le conteneur
bash

docker run --rm -p 8080:80 --env-file .env.docker tracktoon:latest
4. Accéder au site
text

http://localhost:8080
🧪 Tests
Les tests unitaires sont situés dans le dossier test/.

Pour les exécuter :

bash

./vendor/bin/phpunit
ou, selon la config :

bash

php vendor/bin/phpunit
🗄️ Base de données (schéma)
Le schéma est compatible MySQL / TiDB.

Tables principales :

users : utilisateurs (id, pseudo, mail, mot de passe hashé, rôle)

books : œuvres (titre, type, description, image, chapitre, auteur)

genders : genres

books_genders : table de liaison livres ↔ genres

library : bibliothèque par utilisateur (statut, favori, commentaire)

scores : notes (score) par utilisateur / livre

users_books : autre table de liaison utilisateur / livre

Un script SQL complet (adapté à TiDB) est utilisé pour créer la base et insérer les données d’exemple.

🚀 Déploiement
1. Build & push de l’image Docker
bash

docker build -t tracktoon:latest .
docker tag tracktoon:latest <dockerhub_user>/tracktoon:1.0.1
docker push <dockerhub_user>/tracktoon:1.0.1
2. Service web Render
Créer un Web Service sur Render à partir d’une Existing image :

Image : docker.io/<dockerhub_user>/tracktoon:1.0.1

Port : 80

Instance type : Free

Dans l’onglet Environment, définir les mêmes variables que dans .env.docker, mais adaptées à la prod :

dotenv

APP_ENV=prod
APP_DEBUG=false

DB_HOST=<host_tidb>
DB_PORT=4000
DB_NAME=test
DB_USER=<user_tidb>
DB_PASSWORD=<password_tidb>
DB_CHARSET=utf8mb4
DB_SSL_CA_PATH=/etc/ssl/certs/ca-certificates.crt

MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=
MAIL_FROM_NAME=
MAIL_TO=
Laisser Render déployer, puis accéder à l’URL générée, par exemple :

text

https://tracktoon-1-0-1.onrender.com/
🌍 Nom de domaine
Par défaut, Render fournit une URL du type :

text

https://tracktoon-1-0-1.onrender.com/
Pour utiliser un domaine personnalisé (par exemple https://www.tracktoon.com) :

Acheter le domaine chez un registrar (OVH, Gandi, Namecheap…).

Ajouter ce domaine dans l’onglet Custom Domains du service Render.

Créer les entrées DNS nécessaires (CNAME, etc.) côté registrar.

📌 Notes
Les fichiers .env et .env.docker ne sont pas commités dans le dépôt (ajoutés dans .gitignore).

La configuration TLS pour TiDB Cloud est gérée par DB_SSL_CA_PATH et les options PDO.

Le projet a été initialement développé en local sous WAMP, puis migré vers une architecture Docker + Render + TiDB Cloud pour le déploiement.






