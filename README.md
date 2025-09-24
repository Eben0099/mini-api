# Mini API - Guide de démarrage (Docker)

Ce projet est une API Symfony pour la gestion de salons, stylistes et utilisateurs, entièrement dockerisée.

## Prérequis
- Docker & Docker Compose

## Installation et lancement

### 1. Cloner le projet
```bash
git clone <url-du-repo> mini-api
cd mini-api
```

### 2. Lancer les services Docker
```bash
docker compose up -d
```

### 3. Installer les dépendances PHP dans le conteneur
```bash
docker compose exec php-api composer install
```

### 4. Installer les dépendances JS dans le conteneur
```bash
docker compose exec php-api npm install
```

### 5. Lancer les migrations
```bash
docker compose exec php-api php bin/console doctrine:migrations:migrate
```

### 6. Générer les assets front
```bash
docker compose exec php-api npm run build
```

## Ports utilisés
- **8090** : API principale (Symfony, HTTP)
- **8450** : API principale (Symfony, HTTPS)
- **8898** : PhpMyAdmin (gestion BDD)
- **4310** : MySQL (accès local à la BDD)
- **8026** : MailHog (visualisation des emails envoyés)
- **1026** : MailHog (SMTP)

## Accès rapide
- **API** : [http://localhost:8090](http://localhost:8090)
- **API HTTPS** : [https://localhost:8450](https://localhost:8450)
- **Documentation Swagger** : [http://localhost:8090/api/doc](http://localhost:8090/api/doc)
- **PhpMyAdmin** : [http://localhost:8898](http://localhost:8898)
- **MailHog** : [http://localhost:8026](http://localhost:8026)

## Commandes utiles (dans le conteneur PHP)
- Lancer les tests :
  ```bash
  docker compose exec php-api php bin/phpunit
  ```
- Générer les assets front :
  ```bash
  docker compose exec php-api npm run build
  ```
- Accéder au shell du conteneur PHP :
  ```bash
  docker compose exec php-api bash
  ```

## Problèmes fréquents
- **Permissions upload** :
  ```bash
  docker compose exec php-api chmod -R 0777 public/uploads
  ```
- **Extension GD manquante** :
  (Déjà installée dans l'image PHP fournie, sinon adapter le Dockerfile)

## Contact
Pour toute question, contactez l'équipe technique ou consultez la documentation dans le dossier `docs/`.
