# Nouvelles Fonctionnalités - API Client Management

## 📋 Résumé des fonctionnalités implémentées

### 1. Pagination des clients ✅

**Endpoint**: `GET /api/clients`

**Paramètres de requête**:
- `page` (optionnel, défaut: 1) - Numéro de la page
- `limit` (optionnel, défaut: 10, max: 100) - Nombre d'éléments par page

**Exemple de requête**:
```bash
curl -X GET "http://localhost/api/clients?page=1&limit=20" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Réponse**:
```json
{
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "totalPages": 8
  }
}
```

---

### 2. Gestion des rôles et permissions ✅

#### Hiérarchie des rôles

**ROLE_SUPER_ADMIN** (Accès complet)
- ✅ Créer des clients (`POST /api/clients`)
- ✅ Modifier des clients (`PUT /api/clients/{id}`)
- ✅ Supprimer des clients (`DELETE /api/clients/{id}`)
- ✅ Voir la liste des clients (`GET /api/clients`)
- ✅ Voir le détail d'un client (`GET /api/clients/{id}`)
- ✅ Télécharger les documents d'identité
- ✅ Créer de nouveaux utilisateurs (`POST /api/register`)

**ROLE_ADMIN** (Accès en lecture seule)
- ✅ Voir la liste des clients (`GET /api/clients`)
- ✅ Voir le détail d'un client (`GET /api/clients/{id}`)
- ✅ Télécharger les documents d'identité
- ❌ Pas de création, modification ou suppression

**ROLE_USER** (Accès au profil uniquement)
- ✅ Gérer son profil utilisateur (`GET/PUT /api/profile`)
- ✅ Modifier sa photo de profil
- ❌ Pas d'accès aux clients

#### Configuration dans `config/packages/security.yaml`

Les access_control ont été activés et configurés :
```yaml
access_control:
    # Profil utilisateur - tous les utilisateurs authentifiés
    - { path: ^/api/profile, roles: ROLE_USER }

    # API CLIENTS - Lecture (ADMIN et SUPER_ADMIN)
    - { path: ^/api/clients$, roles: ROLE_ADMIN, methods: [GET] }
    - { path: ^/api/clients/\d+$, roles: ROLE_ADMIN, methods: [GET] }
    - { path: ^/api/clients/\d+/identity-proofs/\d+/download$, roles: ROLE_ADMIN, methods: [GET] }

    # API CLIENTS - Création, modification, suppression (SUPER_ADMIN uniquement)
    - { path: ^/api/clients, roles: ROLE_SUPER_ADMIN, methods: [POST, PUT, DELETE] }

    # API REGISTER - Réservé au super admin
    - { path: ^/api/register, roles: ROLE_SUPER_ADMIN }
```

---

### 3. Profil utilisateur avec photo ✅

#### Nouveaux endpoints

**GET /api/profile** - Afficher le profil
```bash
curl -X GET "http://localhost/api/profile" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Réponse**:
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "roles": ["ROLE_USER"],
  "profilePicturePath": "/uploads/user_profile_abc123.jpg"
}
```

**PUT /api/profile** - Mettre à jour le profil
```bash
curl -X PUT "http://localhost/api/profile" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "firstName=Jane" \
  -F "lastName=Smith" \
  -F "email=jane.smith@example.com" \
  -F "profilePicture=@/path/to/photo.jpg"
```

#### Entité User mise à jour
- Ajout du champ `profilePicturePath` (string, nullable)
- Gestion des uploads d'images (jpg, png, gif, webp, avif)

---

### 4. Téléchargement de documents & statistiques ✅

#### Endpoint de téléchargement

**GET /api/clients/{clientId}/identity-proofs/{proofId}/download**

**Fonctionnalités**:
- ✅ Télécharge le document d'identité (PDF ou image)
- ✅ Incrémente automatiquement le compteur `downloadCount`
- ✅ Accessible aux ADMIN et SUPER_ADMIN

**Exemple de requête**:
```bash
curl -X GET "http://localhost/api/clients/1/identity-proofs/5/download" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --output document.pdf
```

#### Compteur de téléchargements

**Entité IdentityProof mise à jour**:
- Nouveau champ `downloadCount` (int, défaut: 0)
- Méthode `incrementDownloadCount()` pour incrémenter le compteur
- Le compteur est exposé dans le groupe de sérialisation `client`

**Visualisation dans le détail du client**:
```json
{
  "id": 1,
  "name": "Client Name",
  "identityProofs": [
    {
      "id": 5,
      "type": "passport",
      "filePath": "/uploads/identity_abc123.pdf",
      "status": "approved",
      "downloadCount": 12
    }
  ]
}
```

**Cas d'usage**:
- Tracking du nombre de consultations d'un document
- Indicateur de popularité/utilisation d'un document
- Comptabilisation des "ventes" ou utilisations du document

---

## 🗄️ Migrations de base de données

**Fichier**: `migrations/Version20260203125602.php`

**Modifications**:
```sql
-- Ajouter le compteur de téléchargements aux documents d'identité
ALTER TABLE identity_proof ADD download_count INT NOT NULL DEFAULT 0;

-- Ajouter la photo de profil aux utilisateurs
ALTER TABLE user ADD profile_picture_path VARCHAR(255) DEFAULT NULL;
```

**Commande d'exécution**:
```bash
php bin/console doctrine:migrations:migrate
```

---

## 📝 Contrôleurs mis à jour

### ClientController.php
- ✅ Pagination ajoutée à `list()`
- ✅ Attributs `#[IsGranted()]` activés sur toutes les méthodes
- ✅ Nouvelle route `downloadIdentityProof()` avec incrémentation du compteur

### ProfileController.php (nouveau)
- ✅ `show()` - Afficher le profil utilisateur
- ✅ `update()` - Mettre à jour le profil et la photo
- ✅ Protection par `#[IsGranted('ROLE_USER')]`

---

## 🔐 Tests des rôles

### Créer des utilisateurs de test avec différents rôles

**Super Admin**:
```bash
php bin/console app:create-user superadmin@example.com password123 --roles=ROLE_SUPER_ADMIN
```

**Admin**:
```bash
php bin/console app:create-user admin@example.com password123 --roles=ROLE_ADMIN
```

**User**:
```bash
php bin/console app:create-user user@example.com password123
```

### Scénarios de test

1. **SUPER_ADMIN peut tout faire**
   - Créer/modifier/supprimer des clients ✅
   - Voir la liste et les détails ✅
   - Télécharger des documents ✅

2. **ADMIN peut uniquement consulter**
   - Voir la liste des clients ✅
   - Voir le détail d'un client ✅
   - Télécharger des documents ✅
   - Création/modification/suppression ❌ (403 Forbidden)

3. **USER peut gérer son profil**
   - Voir son profil ✅
   - Modifier son profil et photo ✅
   - Accès aux clients ❌ (403 Forbidden)

---

## 📊 Swagger/OpenAPI

Tous les nouveaux endpoints sont documentés avec des annotations OpenAPI :
- Paramètres de pagination documentés
- Nouveau endpoint de profil
- Endpoint de téléchargement documenté

**Accès à la documentation** : `http://localhost/api/doc`

---

## ✅ Checklist d'implémentation

- ✅ Pagination des clients avec paramètres `page` et `limit`
- ✅ Activation des rôles SUPER_ADMIN, ADMIN, USER
- ✅ Configuration des access_control dans security.yaml
- ✅ Profil utilisateur avec photo de profil
- ✅ Téléchargement de documents d'identité
- ✅ Compteur de téléchargements avec incrémentation automatique
- ✅ Migrations de base de données
- ✅ Documentation OpenAPI/Swagger
- ✅ Gestion des permissions par rôle

---

## 🚀 Prochaines étapes suggérées

1. **Tests unitaires et fonctionnels**
   - Tester la pagination
   - Tester les permissions par rôle
   - Tester l'incrémentation du compteur

2. **Fonctionnalités additionnelles**
   - Filtres et recherche sur la liste des clients
   - Tri personnalisé (par nom, date, etc.)
   - Export de la liste des clients (CSV, Excel)
   - Statistiques avancées sur les téléchargements

3. **Sécurité**
   - Rate limiting sur les téléchargements
   - Validation avancée des fichiers uploadés
   - Logs d'audit pour les actions sensibles

4. **Performance**
   - Cache sur la liste des clients
   - Optimisation des requêtes avec jointures
   - CDN pour les fichiers statiques
