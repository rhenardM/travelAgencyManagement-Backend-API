# Guide de Test Rapide - Nouvelles Fonctionnalités

## 🔧 Préparation

### 1. Créer des utilisateurs de test avec différents rôles

Si vous n'avez pas encore de commande pour créer des utilisateurs, vous pouvez les créer via l'endpoint `/api/register` (en tant que SUPER_ADMIN) ou directement en base de données.

**Exemple d'utilisateurs à créer** :
- `superadmin@test.com` avec rôle `ROLE_SUPER_ADMIN`
- `admin@test.com` avec rôle `ROLE_ADMIN`
- `user@test.com` avec rôle `ROLE_USER`

### 2. Obtenir les tokens JWT

```bash
# Super Admin
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "superadmin@test.com", "password": "password"}'

# Admin
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin@test.com", "password": "password"}'

# User
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "user@test.com", "password": "password"}'
```

Copiez les tokens obtenus pour les tests suivants.

---

## 🧪 Tests de la Pagination

### Test 1 : Liste avec pagination par défaut (page 1, 10 éléments)
```bash
curl -X GET "http://localhost/api/clients" \
  -H "Authorization: Bearer SUPER_ADMIN_TOKEN"
```

**Résultat attendu** : 10 premiers clients avec infos de pagination

### Test 2 : Pagination personnalisée (page 2, 5 éléments)
```bash
curl -X GET "http://localhost/api/clients?page=2&limit=5" \
  -H "Authorization: Bearer SUPER_ADMIN_TOKEN"
```

**Résultat attendu** : 5 clients à partir du 6ème

### Test 3 : Limite maximale (100 éléments)
```bash
curl -X GET "http://localhost/api/clients?page=1&limit=150" \
  -H "Authorization: Bearer SUPER_ADMIN_TOKEN"
```

**Résultat attendu** : Maximum 100 clients (limite appliquée automatiquement)

---

## 🔐 Tests des Permissions

### Test 4 : ADMIN peut voir la liste des clients
```bash
curl -X GET "http://localhost/api/clients" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**Résultat attendu** : ✅ 200 OK - Liste des clients

### Test 5 : ADMIN peut voir le détail d'un client
```bash
curl -X GET "http://localhost/api/clients/1" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**Résultat attendu** : ✅ 200 OK - Détail du client

### Test 6 : ADMIN ne peut PAS créer un client
```bash
curl -X POST "http://localhost/api/clients" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -F "name=Test Client" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "phone=+123456789" \
  -F "email=test@test.com" \
  -F "adresse=123 Test St"
```

**Résultat attendu** : ❌ 403 Forbidden - Access Denied

### Test 7 : ADMIN ne peut PAS modifier un client
```bash
curl -X PUT "http://localhost/api/clients/1" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -F "name=Updated Name"
```

**Résultat attendu** : ❌ 403 Forbidden - Access Denied

### Test 8 : ADMIN ne peut PAS supprimer un client
```bash
curl -X DELETE "http://localhost/api/clients/1" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**Résultat attendu** : ❌ 403 Forbidden - Access Denied

### Test 9 : SUPER_ADMIN peut créer un client
```bash
curl -X POST "http://localhost/api/clients" \
  -H "Authorization: Bearer SUPER_ADMIN_TOKEN" \
  -F "name=New Client" \
  -F "firstName=Jane" \
  -F "lastName=Smith" \
  -F "phone=+987654321" \
  -F "email=jane@example.com" \
  -F "adresse=456 New Ave"
```

**Résultat attendu** : ✅ 201 Created - Client créé

### Test 10 : USER ne peut PAS accéder aux clients
```bash
curl -X GET "http://localhost/api/clients" \
  -H "Authorization: Bearer USER_TOKEN"
```

**Résultat attendu** : ❌ 403 Forbidden - Access Denied

---

## 👤 Tests du Profil Utilisateur

### Test 11 : USER peut voir son profil
```bash
curl -X GET "http://localhost/api/profile" \
  -H "Authorization: Bearer USER_TOKEN"
```

**Résultat attendu** : ✅ 200 OK - Profil de l'utilisateur

### Test 12 : USER peut modifier son profil
```bash
curl -X PUT "http://localhost/api/profile" \
  -H "Authorization: Bearer USER_TOKEN" \
  -F "firstName=UpdatedFirstName" \
  -F "lastName=UpdatedLastName"
```

**Résultat attendu** : ✅ 200 OK - Profil mis à jour

### Test 13 : USER peut uploader une photo de profil
```bash
curl -X PUT "http://localhost/api/profile" \
  -H "Authorization: Bearer USER_TOKEN" \
  -F "profilePicture=@/path/to/your/photo.jpg"
```

**Résultat attendu** : ✅ 200 OK - Photo uploadée et chemin retourné

### Test 14 : ADMIN peut aussi voir son profil
```bash
curl -X GET "http://localhost/api/profile" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**Résultat attendu** : ✅ 200 OK - Profil de l'admin

---

## 📥 Tests du Téléchargement de Documents

### Préparation : Créer un client avec un document d'identité
```bash
curl -X POST "http://localhost/api/clients" \
  -H "Authorization: Bearer SUPER_ADMIN_TOKEN" \
  -F "name=Test Download" \
  -F "firstName=Test" \
  -F "lastName=Download" \
  -F "phone=+111222333" \
  -F "email=testdownload@example.com" \
  -F "adresse=Test Address" \
  -F "identityType=passport" \
  -F "identityFile=@/path/to/document.pdf"
```

Notez l'ID du client et l'ID du document d'identité retournés.

### Test 15 : ADMIN peut télécharger un document
```bash
curl -X GET "http://localhost/api/clients/{CLIENT_ID}/identity-proofs/{PROOF_ID}/download" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  --output downloaded_document.pdf
```

**Résultat attendu** : ✅ 200 OK - Fichier téléchargé

### Test 16 : Vérifier l'incrémentation du compteur
```bash
# Télécharger le document plusieurs fois
curl -X GET "http://localhost/api/clients/{CLIENT_ID}/identity-proofs/{PROOF_ID}/download" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  --output doc1.pdf

curl -X GET "http://localhost/api/clients/{CLIENT_ID}/identity-proofs/{PROOF_ID}/download" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  --output doc2.pdf

curl -X GET "http://localhost/api/clients/{CLIENT_ID}/identity-proofs/{PROOF_ID}/download" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  --output doc3.pdf

# Vérifier le compteur dans le détail du client
curl -X GET "http://localhost/api/clients/{CLIENT_ID}" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

**Résultat attendu** : Le champ `downloadCount` dans `identityProofs` devrait être à 3

### Test 17 : USER ne peut PAS télécharger un document
```bash
curl -X GET "http://localhost/api/clients/{CLIENT_ID}/identity-proofs/{PROOF_ID}/download" \
  -H "Authorization: Bearer USER_TOKEN"
```

**Résultat attendu** : ❌ 403 Forbidden - Access Denied

---

## 📊 Tests de la Documentation Swagger

### Test 18 : Accéder à la documentation
Ouvrez votre navigateur et accédez à :
```
http://localhost/api/doc
```

**Vérifications** :
- ✅ L'endpoint `/api/clients` affiche les paramètres `page` et `limit`
- ✅ Les nouveaux endpoints `/api/profile` sont présents
- ✅ L'endpoint `/api/clients/{clientId}/identity-proofs/{proofId}/download` est documenté
- ✅ Les tags de sécurité sont présents (bearerAuth)

---

## ✅ Résumé des Résultats Attendus

| Test | Utilisateur | Action | Résultat |
|------|-------------|--------|----------|
| 1-3  | SUPER_ADMIN | Pagination | ✅ 200 OK |
| 4-5  | ADMIN | Voir clients | ✅ 200 OK |
| 6-8  | ADMIN | Créer/Modifier/Supprimer | ❌ 403 Forbidden |
| 9    | SUPER_ADMIN | Créer client | ✅ 201 Created |
| 10   | USER | Voir clients | ❌ 403 Forbidden |
| 11-13| USER | Gérer profil | ✅ 200 OK |
| 14   | ADMIN | Voir profil | ✅ 200 OK |
| 15   | ADMIN | Télécharger doc | ✅ 200 OK |
| 16   | ADMIN | Compteur | ✅ Incrémenté |
| 17   | USER | Télécharger doc | ❌ 403 Forbidden |
| 18   | - | Swagger | ✅ Documenté |

---

## 🐛 Debugging

Si vous rencontrez des erreurs :

### 403 Forbidden inattendu
```bash
# Vérifier les rôles de l'utilisateur
php bin/console security:encode-password

# Vérifier la configuration security.yaml
cat config/packages/security.yaml
```

### Fichier non trouvé lors du téléchargement
```bash
# Vérifier que le répertoire uploads existe
ls -la public/uploads/

# Vérifier les permissions
chmod -R 775 public/uploads/
```

### Migration non appliquée
```bash
# Vérifier l'état des migrations
php bin/console doctrine:migrations:status

# Appliquer les migrations manquantes
php bin/console doctrine:migrations:migrate
```

---

## 📝 Notes

- Remplacez `localhost` par votre domaine si nécessaire
- Remplacez `{CLIENT_ID}` et `{PROOF_ID}` par les IDs réels
- Les tokens JWT expirent après un certain temps (vérifiez votre configuration dans `lexik_jwt_authentication.yaml`)
- Pour les tests avec des fichiers, assurez-vous d'avoir des fichiers de test (PDF, images) à disposition
