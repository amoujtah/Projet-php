<div align="center">

# 🍽️ RestauManager

### Application de Gestion de Restaurant Full-Stack

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![Doctrine](https://img.shields.io/badge/Doctrine_ORM-2.x-FC6A31?style=for-the-badge&logo=doctrine&logoColor=white)](https://www.doctrine-project.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Twig](https://img.shields.io/badge/Twig-3.x-bacf29?style=for-the-badge&logo=symfony&logoColor=black)](https://twig.symfony.com/)
[![GitHub](https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/)

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](CONTRIBUTING.md)
[![Made with ❤️](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F-red?style=flat-square)](https://github.com/)

</div>

---

## 📖 Description du projet

**RestauManager** est une application web full-stack de gestion de restaurant développée avec **Symfony 7** et **PHP 8.3**. Elle centralise la gestion des tables, des commandes, du menu, des réservations et du personnel dans une interface claire et intuitive.

Que vous soyez débutant en développement Symfony ou développeur expérimenté, RestauManager est conçu pour être lisible, extensible et facile à prendre en main.

> 💡 **Backend Symfony** gère la logique métier et l'accès aux données via Doctrine ORM · **Twig** rend les vues côté serveur · **MySQL** assure la persistance.

---

## 🛠️ Stack technique

| Couche | Technologie | Rôle |
|---|---|---|
| 🐘 **Langage** | PHP 8.3 | Langage serveur principal |
| ⚡ **Framework** | Symfony 7 | Architecture MVC, routing, sécurité |
| 🗄️ **ORM** | Doctrine 2 | Mapping objet-relationnel & migrations |
| 🎨 **Templates** | Twig 3 | Moteur de rendu HTML côté serveur |
| 💾 **Base de données** | MySQL 8 | Persistance des données |
| 🔐 **Auth** | Security Bundle | Authentification & rôles |
| 🐙 **Versioning** | GitHub | Contrôle de version & collaboration |

---

## 📁 Structure du projet

```
restaurant/
├── 📂 config/
│   ├── packages/          # Config des bundles (security, doctrine…)
│   ├── routes.yaml        # Déclaration des routes
│   └── services.yaml      # Injection de dépendances
│
├── 📂 migrations/         # Migrations Doctrine (versioning BDD)
│
├── 📂 public/
│   ├── index.php          # Point d'entrée de l'application
│   └── assets/            # CSS, JS, images publics
│
├── 📂 src/
│   ├── Controller/
│   │   ├── DashboardController.php
│   │   ├── TableController.php        # Gestion des tables
│   │   ├── CommandeController.php     # Commandes en salle
│   │   ├── MenuController.php         # Carte & plats
│   │   ├── ReservationController.php  # Réservations clients
│   │   └── PersonnelController.php    # Gestion du staff
│   ├── Entity/
│   │   ├── Table.php                  # Entité table
│   │   ├── Commande.php               # Entité commande
│   │   ├── MenuItem.php               # Entité plat/boisson
│   │   ├── Reservation.php            # Entité réservation
│   │   └── User.php                   # Entité utilisateur/staff
│   ├── Repository/
│   │   ├── TableRepository.php
│   │   ├── CommandeRepository.php
│   │   └── MenuItemRepository.php
│   ├── Form/
│   │   ├── CommandeType.php
│   │   ├── MenuItemType.php
│   │   └── ReservationType.php
│   └── Security/
│       └── AppAuthenticator.php
│
├── 📂 templates/
│   ├── base.html.twig                 # Layout principal
│   ├── dashboard/
│   ├── table/
│   ├── commande/
│   ├── menu/
│   └── reservation/
│
├── 📄 .env                            # Variables d'environnement
├── 📄 composer.json                   # Dépendances PHP
└── 📄 README.md                       # Vous êtes ici !
```

---

## ⚙️ Installation & Configuration

> **Prérequis :** PHP 8.3+, Composer, Symfony CLI et MySQL doivent être installés.

### 1️⃣ Cloner le projet

```bash
git clone https://github.com/votre-username/restaumanager.git
cd restaumanager
```

### 2️⃣ Installer les dépendances PHP

```bash
composer install
```

### 3️⃣ Configurer les variables d'environnement

Copiez le fichier `.env` et adaptez-le à votre environnement :

```bash
cp .env .env.local
```

Puis éditez `.env.local` :

```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/restaumanager?serverVersion=8.0"
APP_ENV=dev
APP_SECRET=votre_secret_ici
```

### 4️⃣ Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 5️⃣ Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 6️⃣ (Optionnel) Charger les données de démonstration

```bash
php bin/console doctrine:fixtures:load
```

### 7️⃣ Démarrer le serveur Symfony

```bash
symfony server:start
```

> 🚀 L'application est maintenant accessible sur **http://localhost:8000**

---

## 🌍 URLs de l'application

| Page | URL | Description |
|---|---|---|
| 🏠 Dashboard | `http://localhost:8000/admin/dashboard` | Vue d'ensemble |
| 🪑 Tables | `http://localhost:8000/admin/tables` | Plan de salle |
| 🧾 Commandes | `http://localhost:8000/admin/orders` | Commandes en cours |
| 📖 Menu | `http://localhost:8000/admin/menu` | Carte & plats |
| 📅 Réservations | `http://localhost:8000/admin/reservations` | Réservations clients |
| 👥 Personnel | `http://localhost:8000/admin/staff` | Gestion du staff |
| 📊 Statistiques | `http://localhost:8000/admin/stats` | CA & rapports |
| 🔧 Admin Doctrine | `http://localhost:8000/_profiler` | Débogueur Symfony |

---

## ✨ Fonctionnalités

- 🔐 **Authentification** — Connexion sécurisée avec rôles (Admin, Serveur, Cuisinier)
- 🪑 **Gestion des tables** — Plan de salle interactif, statuts (libre / occupée / réservée)
- 🧾 **Commandes** — Création, suivi en temps réel et facturation des commandes
- 📖 **Menu & carte** — CRUD complet des plats avec catégories et gestion des stocks
- 📅 **Réservations** — Gestion des réservations clients avec créneaux horaires
- 👥 **Personnel** — Gestion des employés et attribution des rôles
- 📊 **Statistiques** — Tableau de bord avec chiffre d'affaires, couverts servis, rotations
- 🖨️ **Tickets** — Génération de tickets de caisse et bons de cuisine

---

## 🗄️ Schéma de la base de données

```
┌─────────────┐     ┌──────────────┐     ┌───────────────┐
│   Table      │────▶│   Commande   │────▶│  LigneCommande│
│─────────────│     │──────────────│     │───────────────│
│ id           │     │ id           │     │ id            │
│ numero       │     │ table_id     │     │ commande_id   │
│ capacite     │     │ statut       │     │ menu_item_id  │
│ statut       │     │ total        │     │ quantite      │
│ zone         │     │ created_at   │     │ prix_unitaire │
└─────────────┘     └──────────────┘     └───────────────┘
                                                │
┌─────────────┐     ┌──────────────┐            ▼
│ Reservation  │     │   MenuItem   │◀───────────┘
│─────────────│     │──────────────│
│ id           │     │ id           │
│ client_nom   │     │ nom          │
│ table_id     │     │ categorie    │
│ nb_personnes │     │ prix         │
│ date_heure   │     │ stock        │
│ statut       │     │ disponible   │
└─────────────┘     └──────────────┘
```

---

## 🐙 Mise en place GitHub

```bash
# Initialiser le dépôt Git
git init

# Ajouter tous les fichiers
git add .

# Premier commit
git commit -m "feat: initialisation du projet RestauManager"

# Renommer la branche principale
git branch -M main

# Lier votre dépôt distant (remplacez par votre URL)
git remote add origin VOTRE_URL_GITHUB

# Pousser vers GitHub
git push -u origin main
```

> 💡 Remplacez `VOTRE_URL_GITHUB` par l'URL de votre dépôt, ex. `https://github.com/votre-username/restaumanager.git`

---

## 🔐 Variables d'environnement

Créez un fichier `.env.local` à la racine — **ne le commitez jamais !**

```env
# Application
APP_ENV=dev
APP_SECRET=changez_cette_valeur_en_production

# Base de données MySQL
DATABASE_URL="mysql://root:password@127.0.0.1:3306/restaumanager?serverVersion=8.0&charset=utf8mb4"

# Mailer (pour les confirmations de réservation)
MAILER_DSN=smtp://localhost:25

# Paramètres du restaurant
RESTAURANT_NOM="Le Gourmet"
RESTAURANT_ADRESSE="12 Rue de la Paix, Paris"
TVA_TAUX=10
```

Ajoutez ces lignes à votre `.gitignore` :

```bash
# .gitignore
.env.local
.env.*.local
/vendor/
/var/
*.cache
```

---

## 🧪 Tests

Lancez la suite de tests avec PHPUnit :

```bash
# Tous les tests
php bin/phpunit

# Tests d'une entité spécifique
php bin/phpunit tests/Entity/CommandeTest.php

# Tests avec couverture de code
php bin/phpunit --coverage-html coverage/
```

---

## 🚀 Améliorations futures

- [ ] 📱 **Application mobile** — Interface PWA pour les serveurs en salle
- [ ] 🖨️ **Impression cuisine** — Envoi automatique des bons au pôle cuisine
- [ ] 📊 **Rapports avancés** — Exports PDF/Excel du chiffre d'affaires
- [ ] 🔔 **Notifications temps réel** — Alertes via Mercure ou WebSockets
- [ ] 💳 **Intégration paiement** — Terminal de paiement Stripe
- [ ] 🌐 **Multi-restaurants** — Gestion de plusieurs établissements
- [ ] 🐳 **Docker** — Conteneurisation pour déploiement simplifié
- [ ] ☁️ **Déploiement cloud** — Guide Railway / PlanetHoster / OVH

---

## 📄 Licence

Ce projet est distribué sous licence **MIT** — libre d'utilisation, modification et distribution.

```
MIT License

Copyright (c) 2024 RestauManager Contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software.
```

Voir le fichier [LICENSE](LICENSE) pour les détails complets.

---

<div align="center">

Fait avec ❤️ par l'équipe **RestauManager**

⭐ Si ce projet vous est utile, n'oubliez pas de lui mettre une étoile sur GitHub !

</div>
