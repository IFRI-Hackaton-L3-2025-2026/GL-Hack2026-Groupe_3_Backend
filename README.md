# AI4BMI Platform — Groupe 3

> Plateforme logicielle complète pour la gestion industrielle et le e-commerce — Hackathon AI4BMI 2026

## Contexte du Projet

Ce projet a été développé dans le cadre du **Hackathon AI4BMI**, organisé par l'Institut de Formation et de Recherche en Informatique (IFRI-UAC), sous la supervision de **Dr (MC) Ratheil HOUNDJI** et **Ing Linuse TIKPON**. Il répond au défi posé par l'usine **Bénin Moto Industry (BMI)**, située à Glo-Djigbé, qui produit des pièces automobiles et motocyclettes à l'aide d'équipements industriels automatisés (robots KUKA, CNC FANUC, presses SCHULER, convoyeurs Siemens).

La solution développée est une **plateforme logicielle modulaire en trois dépôts**, combinant :

-   Un **backend central** (API REST Laravel — PHP)
-   Un **système web de gestion des équipements industriels** (React / TypeScript)
-   Un **module e-commerce** avec **application mobile** cliente (Flutter) et tableau de bord administrateur (React / TypeScript)

Le hackathon s'est déroulé du **16 février 2026** au **24 février 2026**.

### Équipe de Développement

-   **AKAKPO Godwin**  : `[Godwin-AKAKPO]`
    
-   **GUEZODJE Férréol**  : `[ferreol04]`
    
-   **LAWANI Hamzath**  : `[Eden104]`
    
-   **SAGBO Guillaume**  : `[Sg-Guy]`
    

----------

## Répartition des Tâches


| Membre | Tâches assignées et réalisées | Participation |
|--------|------------------|--------------|
| **AKAKPO Godwin** |- Analyse et Conception du Système<br> - Conception et normalisation de la base de données <br> - Développement de l'interface web de gestion des équipements (Module 1 — React)<br> - Développement de l'interface web d'administration BMIShopAdmin (React)<br>- Intégration des interfaces web des modules 1 et 2 au backend <br> - Implémentation de la logique de changement de mot de passe au backend <br> - Déploiement des différents modules sur Render (API, Module 1 et 2)<br> -Rédaction de la documentation technique du projet| **25%** |
| **GUEZODJE Férréol**   |- Analyse et Conception du Système<br> - Conception et normalisation de la base de données <br>- Développement du backend : API Laravel (Module 2) <br> -API REST avec les endpoints : Authentification ( Inscription client, Connexion, Déconnexion, Consultation du profil connecté, Réinitialisation du mot de passe par OTP), Gestion des catégories de produits, Gestion des produits, Panier, Commandes, Simulation des Paiements, Gestion des clients, Dashboard E-commerce<br>- Documentation de l'API avec Swagger|**25%** |
| **LAWANI Hamzath**  |- Analyse et Conception du Système<br> - Conception et normalisation de la base de données <br>  - Développement du backend : API Laravel (Module 1) <br> - API REST avec les endpoints : authentification, gestion des rôles, permissions et accès; gestion des equipements, gestion et signalement des pannes, planification des maitenances; gestion du staff technique; Tableau de bord de gestion .<br>  - Documentation de l'API avec Swagger| **25%** | 
| **SAGBO Guillaume** |- Analyse et Conception du Système<br> - Conception et normalisation de la base de données <br>   - Développement de l'interface de l'application mobile cliente BMIShop (Flutter)<br>- Acceuil - Onboarding - Inscription - Connexion -Catalogue produits, panier, commandes, simulation de paiement, gestion du profil utilisateur, Récupération de compte après oublie de mot de passe <br> - Intégration de l'application mobile au backend <br> -Montage de la vidéo de présentation de la démo | **25%** |
## Objectifs du Projet

### Objectif Principal

Développer une plateforme logicielle complète permettant à BMI de **gérer ses équipements industriels** de façon proactive et de **commercialiser ses produits finis** via un canal numérique moderne et automatisé.

### Objectifs Spécifiques

-   Concevoir une architecture logicielle complète avec séparation backend / frontends
-   Développer un système web de gestion des équipements industriels avec suivi en temps réel
-   Concevoir un module e-commerce complet (application mobile Flutter + interface web admin React)
-   Mettre en place une API REST documentée (Swagger/OpenAPI)
-   Assurer la cohérence et la normalisation de la base de données 
-   Implémenter l'authentification et la gestion des rôles (Admin, Technicien, Gestionnaire, Client)
-   Déployer l'ensemble de la plateforme en ligne 
-   Produire une application robuste, fonctionnelle et bien documentée

----------

## Fonctionnalités Principales

### Module 1 — Gestion des Équipements 

-   **Authentification sécurisée** : Connexion avec gestion des rôles Admin / Technicien / Gestionnaire
-   **Tableau de bord dynamique** : Vue en temps réel des machines actives, en maintenance et en panne
-   **CRUD des équipements** : Création, modification, suppression des machines avec numéro de série unique
-   **Fiche détaillée par machine** : Marque, modèle, localisation, statut, date d'installation, description
-   **Gestion des maintenances** : Planification (préventive / corrective), suivi du statut, coût et dates
-   **Historique des pannes** : Signalement, priorité (faible / moyenne / critique), suivi de résolution
- **Gestion du staff technique**: Réservée à l'administrateur uniquement 
- **Paramètres** : Informations Profil et Changement de mot de passe et de thème  

### Module 2 — E-Commerce

#### BMIShop — Application Mobile Cliente

-   **Inscription / Connexion** : Authentification sécurisée via API
-   **Catalogue produits** : Navigation par catégories (Pièces moteur, Carrosserie, Accessoires moto)
-   **Panier** : Ajout/retrait d'articles, gestion des quantités
-   **Validation de commande** : Choix du mode de livraison (retrait / livraison à domicile)
-   **Simulation de paiement** : Paiement simulé avec génération de référence de transaction
-   **Historique des achats** : Suivi des commandes et de leurs statuts
-   **Gestion du profil** : Informations du profil, historiques de paiements et autres 

#### BMIShopAdmin — Interface Web d'Administration (Admin Uniquement)

-   **Tableau de bord** : Indicateurs clés (ventes, stocks, commandes en attente)
-   **Gestion des produits et catégories** : CRUD complet avec images et gestion du stock
-   **Suivi des commandes** : Consultation et mise à jour des statuts
-   **Gestion des paiements** : Historique et suivi des transactions
-   **Gestion des utilisateurs** : Consultation du dossier d'un client et blocage d'accès à l'application mobile 

----------

## Architecture Global

<img width="1566" height="1206" alt="Capture d’écran du 2026-02-24 14-45-55" src="https://github.com/user-attachments/assets/2a5470fa-5ee3-46e6-bd1a-a3f54b1c14f5" />

###  Organisation des Dépôts - Groupe 3

| Dépôt | Technologie | URL de déploiement |
| :--- | :--- | :--- |
| **`GL-Hack2026-Groupe_3_Backend`** | PHP 8.3 — Laravel — MySQL | [ API Backend Groupe 3](https://gl-hack2026-groupe-3-backend.onrender.com/api/documentation) |
| **`GL-Hack2026-Groupe_3-Equipements`** | TypeScript — React | [Application Web du module 1](https://gl-hack2026-groupe-3-equipements.onrender.com/) |
| **`GL-Hack2026-Groupe_3_E-Commerce`** | Flutter (mobile) + React (admin) | [Interface d'administration](https://gl-hack2026-groupe-3-e-commerce.onrender.com/) <br> [Application mobile (APK)](https://drive.google.com/file/d/1kJ7m8mpq_3Mr1kvBdD0FzazdiJ2zNL0H/view?usp=drivesdk) |

> **NB :** Pour tester les interfaces, utilisez les identifiants d'admin suivants :
> - **Email :** `admin@bmi.bj`
> - **Mot de passe :** `password_secret`

### Stack Technique Détaillé

**Backend**

```
Langage         : PHP 8.3
Framework       : Laravel
Authentification: Laravel Sanctum (tokens Bearer)
Base de données : MySQL 8.0
Serveur         : Render
Documentation   : Swagger / OpenAPI

```

**Module 1 — Gestion des Équipements**

```
Langage         : TypeScript
Framework       : React -TypeScript - TailwindCSS
Déploiement     : Render

```

**Module 2 — E-Commerce**

```
BMIShop         : Flutter (Dart) — application mobile
BMIShopAdmin    : TypeScript — React — TailwindCSS 
Déploiement     : Render

```

----------

## Modèle de Base de Données

> Base de données
<img width="2518" height="1352" alt="Capture d’écran du 2026-02-24 15-26-09" src="https://github.com/user-attachments/assets/fdfab96a-12f7-438c-8de4-0cfed30ab2eb" />

## Structure des Dépôts

### Architecture du Backend  `(GL-Hack2026-Groupe_3_Backend)`

Le projet est basé sur **Laravel**, structuré de manière à séparer la logique métier, l'accès aux données et les points d'entrée API.

```text
.
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Logique de traitement des requêtes (Auth, Équipements, Ventes)
│   │   └── Middleware/        # Filtres de sécurité (ex: CheckRole.php)
│   ├── Models/                # Définition des entités (User, Equipment, Order, etc.)
│   └── Providers/             # Enregistrement des services (AppServiceProvider.php)
├── bootstrap/                 # Fichiers d'initialisation du framework
├── config/                    # Fichiers de configuration (database, auth, etc.)
├── database/
│   ├── factories/             # Générateurs de données de test (UserFactory.php)
│   ├── migrations/            # Schémas de la base de données (17 tables)
│   └── seeders/               # Remplissage initial de la base
├── routes/
│   ├── api.php                # Définition des points d'entrée API REST
│   ├── web.php                # Routes pour l'interface web 
│   └── console.php            # Commandes artisan personnalisées
├── storage/                   # Logs et fichiers uploadés
├── tests/                     # Tests automatisés (Unit & Feature)
├── .env                       # Variables d'environnement (Configuration DB/API)
├── Dockerfile                 # Configuration pour le déploiement conteneurisé
└── artisan                    # Interface en ligne de commande de Laravel
```


###  Architecture du Frontend du module 1(`GL-Hack2026-Groupe_3-Equipements`)

L'application est développée avec **React 18** et **TypeScript**, utilisant **Vite** comme outil de build et **TailwindCSS** pour le style
```
├── src/
│   ├── api/                   # (axios.js, auth, breakdown, dashboard, equipment, maintenance, staff)
│   ├── components/            # Composants UI (dashboard, equipment, layout, ui, NavLink.tsx)
│   ├── contexts/              # Gestion d'état global (AuthContext.tsx)
│   ├── hooks/                 # Hooks personnalisés (use-mobile.ts)
│   ├── lib/                   # Utilitaires (utils.ts)
│   ├── pages/                 # Composants de pages (Equipment.tsx, etc.)
│   ├── App.tsx                # Configuration du Router et des Providers
│   └── main.tsx               # Point d'entrée de l'application
├── public/                    # Assets statiques
├── tailwind.config.ts         # Configuration du design system
├── bun.lockb                  # Lockfile Bun
├── package.json               # Dépendances et scripts
└── README.md                  # Documentation du projet
```


### Architecture Frontend du module 2 (`GL-Hack2026-Groupe_3_E-Commerce`)

Ce dépôt est un monorepo contenant l'application mobile client et l'interface d'administration web.

---

####  BMIShop (Mobile - Flutter)
L'application mobile utilise une architecture structurée pour la gestion du catalogue et des commandes.

```text
BMIShop/
├── lib/
│   ├── data/                 # Couche de données (statiques et locales)
│   ├── local_storage/        # Gestion du cache et des préférences
│   ├── models/               # Entités métier (Product, Order, User, Payment)
│   ├── repos/                # Répertoires de médiation des données
│   ├── screens/              # Pages de l'application (Catalogue, Panier, Profil)
│   ├── services/             # Services de communication avec l'API Laravel
│   ├── theme/                # Configuration visuelle et design system
│   ├── widgets/              # Composants UI réutilisables
│   ├── main.dart             # Point d'entrée de l'application
│   └── navigation.dart       # Gestionnaire de routage
├── assets/                   # Images, icônes et polices
├── pubspec.yaml              # Dépendances Flutter
└── README.md
```
----------
#### BMIShopAdmin (Web Admin - React/TS)

Interface de gestion avancée pour les administrateurs (stocks, commandes, paiements).
```
BMIShopAdmin/
├── src/
│   ├── api/                  # axios.js (Service principal d'appel API) + (services/)
│   ├── components/           # UI (dashboard, layout, shared, ui, NavLink.tsx)
│   ├── contexts/             # Gestion de l'état global (AuthContext, etc.)
│   ├── data/                 # Constantes et mock data
│   ├── hooks/                # Hooks personnalisés (use-mobile.ts)
│   ├── lib/                  # Utilitaires techniques (utils.ts)
│   ├── pages/                # Vues : Dashboard, Produits, Commandes, Paiements
│   ├── App.tsx               # Racine de l'application (Routes & Providers)
│   └── main.tsx              # Point d'entrée React
├── public/                    # Assets statiques
├── tailwind.config.ts         # Configuration Tailwind CSS
├── bun.lockb                  # Lockfile Bun
├── package.json               # Dépendances et scripts
└── README.md
```
## Installation et Configuration Locale

### Prérequis

-   PHP 8.3 + Composer
-   Node.js 18+ + npm
-   Flutter SDK 3.x + Dart
-   MySQL 8.0
-   Git

### 1. Backend

```bash
git clone https://github.com/IFRI-Hackaton-L3-2025-2026/GL-Hack2026-Groupe_3_Backend.git
cd GL-Hack2026-Groupe_3_Backend

composer install
cp .env.example .env
# Configurer DB_DATABASE=hackGL2026, DB_USERNAME, DB_PASSWORD dans .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
# API disponible sur http://localhost:8000/api/v1

```

### 2. Module 1 — Gestion des Équipements

```bash
git clone https://github.com/IFRI-Hackaton-L3-2025-2026/GL-Hack2026-Groupe_3-Equipements.git
cd GL-Hack2026-Groupe_3-Equipements

npm install

npm run dev

```

### 3. Module 2 — E-Commerce

```bash
git clone https://github.com/IFRI-Hackaton-L3-2025-2026/GL-Hack2026-Groupe_3_E-Commerce.git
cd GL-Hack2026-Groupe_3_E-Commerce

# Application mobile Flutter (BMIShop)
cd BMIShop
flutter pub get
flutter run

# Interface web admin React (BMIShopAdmin)
cd ../BMIShopAdmin
npm install

npm run dev

```

----------

## Liens & Ressources


-   **Backend API** : [Cliquez ici](https://gl-hack2026-groupe-3-backend.onrender.com/api)
-   **Documentation API (Swagger)** : [Lien Swagger](https://gl-hack2026-groupe-3-backend.onrender.com/api/documentation)
-   **Module 1 — Gestion des Équipements** : [Cliquez ici](https://gl-hack2026-groupe-3-equipements.onrender.com/)
-   **Module 2 — E-Commerce_Admin** : [Cliquez ici](https://gl-hack2026-groupe-3-e-commerce.onrender.com/)
-   **Application Mobile E-commerce** : [Cliquez ici](https://drive.google.com/file/d/1kJ7m8mpq_3Mr1kvBdD0FzazdiJ2zNL0H/view?usp=drivesdk)
-   **Vidéo de démonstration** : [Lien vidéo](https://drive.google.com/file/d/1BPwXVH3DxIadYgXRDANF5vizTkPhkwL0/view?usp=drivesdk)
-   **Document Technique** : [Lien document](https://drive.google.com/file/d/1SeiweO-83sI3Du-mtmHNqhufPCV-cJGE/view?usp=drive_link)
-   **Rapport de synthèse** : [Lien document](https://drive.google.com/file/d/1FbXsFuegWzYgWHpfk0GLNXU3aFmbSK_1/view?usp=drive_link)
-   
> **NB :** Pour tester les interfaces, utilisez les identifiants d'admin suivants :
> - **Email :** `admin@bmi.bj`
> - **Mot de passe :** `password_secret`
## Licence

Ce projet est réalisé dans le cadre du **Hackathon AI4BMI** organisé par l'**IFRI-UAC** (Institut de Formation et de Recherche en Informatique — Université d'Abomey-Calavi) pour le compte du Semestre 5 du cycle de Licence.

----------

## Remerciements

Un grand merci à :

-   **Dr (MC) Ratheil HOUNDJI** pour l'organisation et l'encadrement du hackathon
-   **Ing Linuse TIKPON** pour le suivi pédagogique et la supervision du projet GL
-   L'**Université d'Abomey-Calavi (UAC)** et l'**IFRI** pour l'opportunité de formation et d'innovation
-   L'usine **Bénin Moto Industry (BMI)** pour avoir inspiré ce défi industriel concret
-   Les communautés **Laravel**,  **Flutter**,  **React.js**,  **TailwindCSS** et  **GetX** pour les documentations et les ressources 

----------

## Contact

**Université d'Abomey-Calavi — IFRI (GL3) | Promotion 2025-2026** Organisation GitHub : [IFRI-Hackaton-L3-2025-2026](https://github.com/IFRI-Hackaton-L3-2025-2026)

-   Enseignant responsable : Dr (MC) Ratheil HOUNDJI — vratheilhoundji@gmail.com
-   Superviseure Projet GL : Ing Linuse TIKPON — linuse.tikpon@gmail.com
