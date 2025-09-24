# Structure de l'API Mini-API

## Architecture

Ce projet suit une architecture API REST moderne avec séparation claire des responsabilités.

## Structure des dossiers

```
src/
├── Controller/
│   ├── Api/                    # Contrôleurs API REST
│   │   ├── UserController.php
│   │   ├── ProductController.php
│   │   └── AuthController.php
│   └── DefaultController.php   # Contrôleur par défaut
│
├── DTO/                        # Data Transfer Objects
│   ├── Request/                # DTOs pour les requêtes
│   │   ├── UserCreateDto.php
│   │   ├── UserUpdateDto.php
│   │   └── LoginRequestDto.php
│   └── Response/               # DTOs pour les réponses
│       ├── UserResponseDto.php
│       ├── ProductResponseDto.php
│       └── ErrorResponseDto.php
│
├── ApiDoc/                     # Documentation API
│   ├── UserApiDoc.php
│   ├── ProductApiDoc.php
│   ├── AuthApiDoc.php
│   └── GlobalApiDoc.php
│
├── Service/                    # Logique métier
│   ├── UserService.php
│   ├── ProductService.php
│   └── AuthService.php
│
├── Entity/                     # Entités Doctrine
│   ├── User.php
│   └── Product.php
│
├── Repository/                 # Repositories Doctrine
│   ├── UserRepository.php
│   └── ProductRepository.php
│
├── DataFixtures/               # Fixtures de données
│   └── AppFixtures.php
│
└── Kernel.php                  # Noyau Symfony
```

## Prochaines étapes recommandées

1. **Créer les entités** :
   - `User` avec les champs email, password, roles, created_at, updated_at
   - `Product` avec les champs name, description, price, created_at, updated_at

2. **Implémenter l'authentification** :
   - Créer `AuthController` avec endpoints login/register
   - Implémenter JWT ou session-based authentication

3. **Développer les contrôleurs API** :
   - CRUD complet pour les utilisateurs
   - CRUD pour les produits
   - Utiliser les DTOs pour validation et transformation

4. **Ajouter les services métier** :
   - Logique de validation
   - Logique de transformation des données
   - Gestion des autorisations

5. **Configurer la documentation** :
   - API Platform ou NelmioApiDocBundle
   - Générer la documentation OpenAPI/Swagger

6. **Tests unitaires et fonctionnels** :
   - Tests pour les contrôleurs
   - Tests pour les services
   - Tests d'intégration pour l'API

## Conventions

- **Contrôleurs API** : Préfixer les routes avec `/api/v1/`
- **DTOs** : Utiliser pour validation et transformation des données
- **Services** : Contiennent la logique métier, injectés dans les contrôleurs
- **Repositories** : Requêtes complexes et optimisations Doctrine

## Technologies utilisées

- **Symfony 6.4** : Framework PHP
- **Doctrine ORM** : Mapping objet-relationnel
- **MySQL 8.0** : Base de données
- **Docker** : Containerisation
- **Composer** : Gestionnaire de dépendances
