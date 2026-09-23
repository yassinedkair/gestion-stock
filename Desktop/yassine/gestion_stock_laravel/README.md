# Gestion Stock — Laravel + MySQL

Cette version remplace le `localStorage` du prototype V11 par une vraie base MySQL et une API Laravel. L'interface reste une application web accessible depuis PC, téléphone et tablette via un lien lorsqu'elle est hébergée.

## 1. Créer l'application Laravel

Le dossier fourni est un **package de migration**. Le plus sûr est de créer une application Laravel propre puis de copier les fichiers de ce package :

```bash
composer create-project laravel/laravel gestion-stock
cd gestion-stock
```

Copie ensuite le contenu de ce package dans le projet Laravel en remplaçant les dossiers/fichiers correspondants.

## 2. MySQL

Crée la base :

```sql
CREATE DATABASE gestion_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis :

```bash
cp .env.example .env
php artisan key:generate
```

Configure dans `.env` :

```env
DB_DATABASE=gestion_stock
DB_USERNAME=root
DB_PASSWORD=
```

## 3. Tables

```bash
php artisan migrate
```

## 4. Lancer

```bash
php artisan serve
```

Puis ouvre :

`http://127.0.0.1:8000`

## Règles métier incluses

- Achat : impossible si le montant dépasse l'argent disponible.
- Charge : même contrôle de trésorerie.
- Vente : impossible si le stock est insuffisant.
- Achat augmente automatiquement le stock.
- Vente diminue automatiquement le stock.
- Salaire : limité au bénéfice disponible.
- Capital initial et apport personnel : mouvement `Prix entrée`.
- Augmentation par bénéfice : augmente le capital sans créer un nouveau mouvement.
- Résultat = entrées - sorties.
- Bénéfice = résultat - capital.
- Les mouvements sont générés depuis les opérations, pas saisis manuellement.
- Les opérations sensibles utilisent des transactions DB et des verrous de lignes pour éviter les incohérences lors de deux actions simultanées.

## Après le prototype

Une fois le système validé par l'entreprise, il faudra ajouter l'authentification, les rôles (Admin/Employé), les sauvegardes MySQL, HTTPS et un hébergement/VPS.
