# Gestion de catalogue produits

Application de gestion d’un catalogue de produits et de catégories, réalisée avec Laravel, MySQL et Vue 3

## Fonctionnalités réalisées

- Liste des produits avec leur catégorie, prix et stock
- Pagination de 10 produits par page
- Recherche par nom
- Création d’un produit
- Modification d’un produit
- Suppression avec confirmation demandé
- Validation des champs et affichage des erreurs
- Gestion des états de chargement et possibilité de réessayer après une erreur
- Interface adaptée aux petits écrans

## Stack et choix techniques

- API Laravel et base de données MySQL, exécutées avec Sail
- Front Vue 3 en Options API, avec Vite, Element Plus et Axios
- Relation entre les produits et les catégories via Eloquent
- Référence/SKU unique, contrôlée par la validation et une contrainte en base
- Prix stocké en `decimal(10, 2)` pour éviter les imprécisions des nombres flottants en base. Il doit être compris entre 0,01 et 99 999 999,99 €, avec deux décimales maximum
- Validation côté front pour guider la saisie, et côté API pour garantir la validité des données
- Formulaire partagé entre la création et la modification
- Chargement des catégories avec les produits pour éviter une requête supplémentaire par ligne
- Le stock doit être un entier positif ou nul

### Prérequis pour installation

- Docker et Docker Compose (Docker Desktop avec WSL2 sous Windows)
- PHP et Composer pour installer les dépendances de l’API, ou un environnement Docker compatible permettant cette installation
- Node.js compatible avec le projet : `^22.18.0` ou `>=24.12.0`
- npm

### API

Depuis la racine du projet :

```bash
cd api
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

Attendre que MySQL soit prêt avant d’exécuter les migrations.

L’API est accessible sur `http://localhost:8000/api`.

Les catégories Informatique, Maison et Loisirs sont ajoutées par le seeder. 
Le catalogue est initialement vide : les produits peuvent être créés depuis l’interface.

### Front

Dans un second terminal, depuis la racine du projet :

```bash
cd front
npm ci
npm run dev
```

L’interface est accessible sur `http://localhost:5173`.

Par défaut, le front appelle `http://localhost:8000/api`. 
Cette adresse peut être remplacée avec la variable `VITE_API_URL` dans un fichier `front/.env`, puis en redémarrant Vite.

Le port 5173 est réservé au front Vue : son exposition a été retirée du fichier Compose de l’API pour éviter un conflit.

### Arrêt

Arrêter le front avec `Ctrl+C`, puis depuis le dossier `api` :

```bash
./vendor/bin/sail down
```

## Routes API

| Méthode | Route | Fonction |
| --- | --- | --- |
| GET | `/api/ping` | Vérification de disponibilité |
| GET | `/api/categories` | Liste des catégories |
| GET | `/api/products` | Liste paginée des produits |
| GET | `/api/products/{product}` | Détail d’un produit |
| POST | `/api/products` | Création d’un produit |
| PATCH | `/api/products/{product}` | Modification d’un produit |
| DELETE | `/api/products/{product}` | Suppression d’un produit |

La liste accepte les paramètres `search` et `page`.

## Vérifications

Le build du front et ESLint ont été exécutés avec succès. 
La modification d’un produit a également été vérifiée manuellement dans l’interface, avec contrôle de la sauvegarde après actualisation.

Depuis le dossier `front` :

```bash
npm run build
npx eslint .
```

Les tests présents dans l’API sont ceux du squelette Laravel. 
Aucun test automatisé spécifique au catalogue n’a encore été ajouté.

## Améliorations envisagées

Avec davantage de temps :

- Ajouter des tests automatisés de l’API, notamment pour la validation, l’unicité du SKU et la pagination
- Ajouter des filtres par catégorie et un tri des produits
- Optimiser le chargement d’Element Plus pour réduire la taille du bundle