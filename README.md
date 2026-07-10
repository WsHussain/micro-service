# MY_MICRO_SERVICES

Introduction aux micro-services — une messagerie découpée en **3 services** :

| Service | Techno | Port | Rôle |
|---|---|---|---|
| `api_php` | PHP · Slim 4 · Eloquent · JWT | `8000` | Users + Messages (SQL) — connexion / inscription |
| `api_express` | Node.js · Express · Mongoose | `5555` | Discussions (MongoDB, NoSQL) |
| `connector` | PHP · Slim 4 · Guzzle | `8080` | Passerelle : relie les deux API, point d'entrée unique |

## Schéma de fonctionnement final

```
                        ┌──────────────────────────┐
   navigateur ────────► │   connector  :8080       │
   Postman / curl       │   (Slim + Guzzle)        │
   client web /client/  │   vérifie le JWT         │
                        └─────┬──────────────┬─────┘
                              │              │
              /register /login│              │/discussions
              /users /messages│              │/discussions/{id}
                              ▼              ▼
                ┌───────────────────┐   ┌────────────────────┐
                │  api_php  :8000   │   │ api_express :5555  │
                │  Slim + Eloquent  │   │ Express + Mongoose │
                │  JWT (login)      │   │  (pas d'auth :     │
                └─────────┬─────────┘   │  jamais exposée    │
                          │             │  directement)      │
                          ▼             └─────────┬──────────┘
                   SQLite / MySQL                 ▼
                  (users, messages)         MongoDB (discussions)
```

Le lien entre les deux mondes : chaque message SQL peut porter un `discussion_id`
(l'ObjectId Mongo d'une discussion). Le connecteur agrège les deux sur
`GET /discussions/{id}/messages` → une discussion (Mongo) + ses messages (SQL)
dans une seule réponse.

## Prérequis

- PHP ≥ 8.1 (testé avec XAMPP 8.2) + Composer
- Node.js ≥ 18 + npm
- MongoDB lancé en local (service Windows ou `mongod`)
- Aucun serveur SQL requis : la BDD PHP est en **SQLite** par défaut
  (passer `DB_DRIVER=mysql` dans `api_php/.env` pour utiliser MySQL)

## Installation

```bash
# 1) API PHP
cd api_php
composer install
composer migrate          # crée users, messages, tests (SQLite)

# 2) API Express
cd ../api_express
npm install

# 3) Connecteur
cd ../connector
composer install
```

Ou en une commande sous Windows : `install_all.bat`
(utilise le PHP de XAMPP, modifiable en tête de script).

> Les `.env` sont fournis avec un `JWT_SECRET` de dev **identique**
> dans `api_php` et `connector` : c'est ce secret partagé qui permet
> au connecteur de vérifier lui-même les tokens.

## Démarrage

`start_all.bat` ouvre les 3 serveurs, ou manuellement :

```bash
cd api_php     && php -S localhost:8000 -t public   # API SQL
cd api_express && node app.js                       # API NoSQL
cd connector   && php -S localhost:8080 -t public   # Passerelle
```

- État global : http://localhost:8080/status
- **Client web (bonus)** : http://localhost:8080/client/

## Les routes

### connector :8080 — point d'entrée unique

| Méthode | Route | Auth | Relayée vers |
|---|---|---|---|
| POST | `/register` | — | api_php |
| POST | `/login` | — | api_php (renvoie le **JWT**) |
| GET | `/me` | JWT | api_php |
| GET/POST | `/users` · PUT/DELETE `/users/{id}` | JWT | api_php |
| GET/POST | `/messages` · PUT/DELETE `/messages/{id}` | JWT | api_php |
| GET/POST | `/discussions` · PUT/DELETE `/discussions/{id}` | JWT | api_express (`created_by` injecté depuis le token) |
| GET | `/discussions/{id}/messages` | JWT | **agrégation** Express + PHP |
| POST | `/discussions/{id}/messages` | JWT | crée le message (PHP) + ajoute l'expéditeur aux participants (Express) |
| GET | `/status` | — | ping des deux API |

Les routes protégées attendent le header `Authorization: Bearer <token>`.

### api_php :8000 (appelable aussi en direct)

`GET /db-test` (query sur la table de test), `POST /register`, `POST /login`,
`GET /me`, CRUD complet `/users` et `/messages` (JWT requis).

`GET /messages?discussion_id=<oid>` filtre les messages d'une discussion.

### api_express :5555 (interne, appelée par le connecteur)

CRUD complet `/discussions` — champs : `title`, `description`,
`participants` (ids des users PHP), `created_by`. Validations via
`express-validator`.

## Exemple de session (curl / Postman)

```bash
# Inscription -> renvoie un token
curl -X POST http://localhost:8080/register -H "Content-Type: application/json" \
     -d "{\"name\":\"Alice\",\"email\":\"alice@test.fr\",\"password\":\"secret123\"}"

# Connexion
curl -X POST http://localhost:8080/login -H "Content-Type: application/json" \
     -d "{\"email\":\"alice@test.fr\",\"password\":\"secret123\"}"
# -> {"message":"Connexion réussie.","user":{...},"token":"eyJ..."}

TOKEN=eyJ...   # le token renvoyé

# Créer une discussion (stockée dans MongoDB)
curl -X POST http://localhost:8080/discussions -H "Authorization: Bearer $TOKEN" \
     -H "Content-Type: application/json" -d "{\"title\":\"Projet\"}"

# Écrire dedans (message stocké en SQL, lié par discussion_id)
curl -X POST http://localhost:8080/discussions/<oid>/messages \
     -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
     -d "{\"content\":\"Premier message\"}"

# Lire la discussion agrégée (Mongo + SQL)
curl http://localhost:8080/discussions/<oid>/messages -H "Authorization: Bearer $TOKEN"
```

## Sécurité

- Mots de passe hachés en **bcrypt** (`password_hash`), jamais renvoyés en JSON.
- Session **stateless** : JWT signé HS256 (`firebase/php-jwt` v7), expiration 24 h.
- L'API Express n'a pas d'authentification : elle n'est jamais exposée au client,
  le connecteur vérifie le JWT (secret partagé) avant de la solliciter.
- Un user ne peut modifier/supprimer que son compte et ses propres messages
  (403 sinon).

## Bonus

- **Messagerie graphique** : http://localhost:8080/client/ — client web
  (HTML/JS vanilla, zéro dépendance) servi par le connecteur : inscription,
  connexion, discussions, envoi/édition/suppression de messages, état des
  services en temps réel dans l'en-tête.

## Choix techniques

- **Slim 4** + **PHP-DI** : le conteneur joue le rôle de Service Factory ;
  Eloquent y est déclaré comme service `db` (`app/dependencies.php`).
- **Eloquent** (illuminate/database) hors Laravel, avec la table de test
  demandée par le sujet (`GET /db-test`).
- **firebase/php-jwt ^7** : la branche 6.x est bloquée par un avis de
  sécurité Packagist (PKSA-y2cr-5h3j-g3ys).
- **SQLite par défaut** pour un rendu qui tourne sans configurer MySQL ;
  bascule MySQL par simple variable d'environnement.
# micro-service
