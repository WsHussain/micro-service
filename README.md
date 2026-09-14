# MY_MICRO_SERVICES

Introduction aux micro-services — une messagerie découpée en **3 services** :

| Service | Techno | Port | Rôle |
|---|---|---|---|
| `api_messages` | PHP · Slim 4 · Eloquent · JWT | `8000` | Users + Messages (MySQL) — connexion / inscription |
| `api_discussions` | Node.js · Express · Mongoose | `5555` | Discussions (MongoDB, NoSQL) |
| `connector` | PHP · Slim 4 · Guzzle | `8080` | Passerelle : relie les deux API |

## Schéma de fonctionnement final

```
        MySQL                                      MongoDB
          │                                           │
          ▼                                           ▼
┌───────────────────┐                     ┌────────────────────┐
│ api_messages :8000│                     │api_discussions :5555│
│ Slim + Eloquent   │                     │ Express + Mongoose │
│ JWT (login)       │                     │                    │
└─────────┬─────────┘                     └──────────┬─────────┘
          │                                          │
          └──────────────┐            ┌──────────────┘
                         ▼            ▼
                  ┌────────────────────────┐
                  │   connector  :8080     │
                  │   (Slim + Guzzle)      │
                  └────────────────────────┘
                              ▲
                              │
                     Postman / curl
```

Le lien entre les deux mondes : une discussion (MongoDB) stocke un tableau
`messageIds` renvoyant vers les messages (MySQL). Le connecteur agrège les deux
sur `GET /discussions/{id}` → la discussion + ses messages complets dans une
seule réponse. Le contenu des messages reste la source de vérité côté MySQL.

## Prérequis

- PHP ≥ 8.1 + Composer
- Node.js ≥ 18 + npm
- MySQL et MongoDB (ou `docker compose up -d`, voir ci-dessous)

## Installation

### 1) Les bases de données

```bash
docker compose up -d
```

Démarre MySQL sur `3306` (base `my_micro_services`, root/root) et MongoDB sur
`27017`. Vous pouvez aussi utiliser vos installations locales — il suffit de
faire correspondre les fichiers `.env`.

### 2) api_messages (port 8000)

```bash
cd api_messages
cp .env.example .env
composer install
php bin/migrate.php        # crée les tables users et messages
php -S 127.0.0.1:8000 -t public
```

### 3) api_discussions (port 5555)

```bash
cd api_discussions
cp .env.example .env
npm install
npm start
```

### 4) connector (port 8080)

```bash
cd connector
cp .env.example .env
composer install
php -S 127.0.0.1:8080 -t public
```

> Pensez à définir un `JWT_SECRET` dans `api_messages/.env` avant toute
> utilisation réelle : la valeur d'exemple est un secret de développement.

## Les routes

### api_messages :8000

| Méthode | Route | Auth | Rôle |
|---|---|---|---|
| POST | `/register` | — | Inscription (renvoie l'user créé) |
| POST | `/login` | — | Connexion — renvoie le **JWT** et l'user |
| GET | `/users` | JWT | Liste des users |
| GET · PUT · DELETE | `/users/{id}` | JWT | Lire / modifier / supprimer un user |
| GET · POST | `/messages` | JWT | Lister ses messages / en créer un |
| GET · PUT · DELETE | `/messages/{id}` | JWT | Lire / modifier / supprimer un message |

Les routes protégées attendent le header `Authorization: Bearer <token>`.
Un user ne peut modifier ou supprimer que son propre compte et ses propres
messages (`403` sinon).

### api_discussions :5555

CRUD complet sur `/discussions` — champs : `title`, `participants` (ids des
users PHP), `messageIds`.

| Méthode | Route | Rôle |
|---|---|---|
| GET · POST | `/discussions` | Lister / créer une discussion |
| GET · PUT · DELETE | `/discussions/{id}` | Lire / modifier / supprimer |
| POST | `/discussions/{id}/messages` | Ajoute un `messageId` à la discussion |

### connector :8080

| Méthode | Route | Auth | Relayée vers |
|---|---|---|---|
| GET · POST | `/discussions` | — | api_discussions |
| GET | `/discussions/{id}` | JWT | **agrégation** Mongo + MySQL |
| POST | `/discussions/{id}/messages` | JWT | crée le message (api_messages) puis ajoute son id à la discussion (api_discussions) |
| DELETE | `/discussions/{id}` | — | api_discussions |

Le connecteur transmet le token reçu à `api_messages`, qui reste seul
responsable de sa vérification.

## Exemple de session (curl / Postman)

```bash
# Inscription puis connexion (directement sur api_messages)
curl -X POST http://127.0.0.1:8000/register -H "Content-Type: application/json" \
     -d '{"username":"alice","email":"alice@test.fr","password":"secret123"}'

curl -X POST http://127.0.0.1:8000/login -H "Content-Type: application/json" \
     -d '{"email":"alice@test.fr","password":"secret123"}'
# -> {"token":"eyJ...","user":{...}}

TOKEN=eyJ...   # le token renvoyé

# Créer une discussion (stockée dans MongoDB)
curl -X POST http://127.0.0.1:8080/discussions -H "Content-Type: application/json" \
     -d '{"title":"Projet","participants":[1]}'

# Écrire dedans : message stocké en MySQL, son id ajouté à la discussion
curl -X POST http://127.0.0.1:8080/discussions/<oid>/messages \
     -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
     -d '{"content":"Premier message"}'

# Lire la discussion agrégée (Mongo + MySQL)
curl http://127.0.0.1:8080/discussions/<oid> -H "Authorization: Bearer $TOKEN"
```

## Correspondance avec le sujet

| Étape | Où |
|---|---|
| 1 — Slim + Eloquent, ajout de l'ORM à la Service Factory | `api_messages/src/Bootstrap/Database.php`, `public/index.php` |
| 1 — `my_first_crud` (modèles User & Message + CRUD) | `api_messages/src/Models`, `src/Controllers`, `src/routes.php` |
| 2 — inscription / connexion + JWT | `api_messages/src/Controllers/AuthController.php`, `src/Support/Jwt.php`, `src/Middleware/JwtAuthMiddleware.php` |
| 3 — Express + Mongoose | `api_discussions/app.js`, `config/db.js` |
| 4 — modèle Discussion + CRUD + validators | `api_discussions/models/`, `controllers/`, `routes/` |
| 5 — connecteur reliant les 2 API | `connector/` |

## Sécurité

- Mots de passe hachés en **bcrypt** (`password_hash`), jamais renvoyés en JSON.
- Session **stateless** : JWT signé HS256 (`firebase/php-jwt` v7). Le token
  porte `sub` (id user) et `exp` ; chaque route protégée le décode.
- L'API Express n'a pas d'authentification : elle n'est pas destinée à être
  exposée directement au client.

## Choix techniques

- **Slim 4** pour les deux services PHP ; Eloquent (`illuminate/database`) est
  utilisé hors Laravel via le Capsule Manager.
- Une discussion stocke `participants` et `messageIds` plutôt que le contenu
  des messages : conformément au schéma du sujet, Messages/MySQL et
  Discussions/MongoDB restent séparés et ne sont reliés que par le connecteur.
- **firebase/php-jwt ^7** : la branche 6.x est bloquée par un avis de sécurité
  Packagist (PKSA-y2cr-5h3j-g3ys).
- Le connecteur n'a **aucune base de données** : il ne fait que dialoguer en
  HTTP (Guzzle) avec les deux API.
