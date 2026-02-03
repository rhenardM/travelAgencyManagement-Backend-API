# Documentation d'intégration Frontend - API Project David

## 🔐 Base URL & Authentification

**Base URL**: `http://localhost:8000/api`

**Authentification**: JWT Bearer Token
- Toutes les routes protégées nécessitent un header: `Authorization: Bearer {token}`
- Le token expire après 1 heure
- Le token est obtenu via l'endpoint `/api/login`

---

## 📋 Table des matières

1. [Authentification](#authentification)
2. [Profil Utilisateur](#profil-utilisateur)
3. [Gestion des Clients](#gestion-des-clients)
4. [Administration](#administration)

---

## 1️⃣ Authentification

### 🔓 Connexion (Login)
```http
POST /api/login
Content-Type: application/json

{
  "username": "superadmin@example.com",
  "password": "superadminpass"
}
```

**Réponse 200 OK:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Exemple JavaScript:**
```javascript
const login = async (email, password) => {
  const response = await fetch('http://localhost:8000/api/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
      username: email, 
      password: password 
    })
  });
  
  const data = await response.json();
  // Sauvegarder le token
  localStorage.setItem('authToken', data.token);
  return data.token;
};
```

---

### 📝 Inscription (Register)
```http
POST /api/register
Authorization: Bearer {token}
Content-Type: application/json

{
  "email": "newuser@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "roles": ["ROLE_USER"]
}
```

**Permissions**: `ROLE_SUPER_ADMIN` uniquement

**Réponse 201 Created:**
```json
{
  "id": 1,
  "email": "newuser@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "roles": ["ROLE_USER"]
}
```

---

### 👤 Informations utilisateur connecté
```http
GET /api/me
Authorization: Bearer {token}
```

**Réponse 200 OK:**
```json
{
  "id": 1,
  "email": "superadmin@example.com",
  "firstName": "Super",
  "lastName": "Admin",
  "roles": ["ROLE_SUPER_ADMIN", "ROLE_USER"]
}
```

---

## 2️⃣ Profil Utilisateur

### 📄 Afficher le profil
```http
GET /api/profile
Authorization: Bearer {token}
```

**Permissions**: Tous les utilisateurs authentifiés (`ROLE_USER`)

**Réponse 200 OK:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "roles": ["ROLE_USER"],
  "profilePicturePath": "/uploads/profile/avatar_123.jpg"
}
```

**Exemple React:**
```javascript
const getProfile = async () => {
  const token = localStorage.getItem('authToken');
  const response = await fetch('http://localhost:8000/api/profile', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return await response.json();
};
```

---

### ✏️ Mettre à jour le profil
```http
PUT /api/profile
Authorization: Bearer {token}
Content-Type: multipart/form-data

firstName=Jane
lastName=Smith
email=jane.smith@example.com
profilePicture=@photo.jpg
```

**Permissions**: Tous les utilisateurs authentifiés (`ROLE_USER`)

**Réponse 200 OK:**
```json
{
  "id": 1,
  "email": "jane.smith@example.com",
  "firstName": "Jane",
  "lastName": "Smith",
  "roles": ["ROLE_USER"],
  "profilePicturePath": "/uploads/profile/avatar_456.jpg"
}
```

**Exemple avec FormData:**
```javascript
const updateProfile = async (firstName, lastName, photoFile) => {
  const token = localStorage.getItem('authToken');
  const formData = new FormData();
  
  formData.append('firstName', firstName);
  formData.append('lastName', lastName);
  if (photoFile) {
    formData.append('profilePicture', photoFile);
  }
  
  const response = await fetch('http://localhost:8000/api/profile', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  return await response.json();
};
```

---

## 3️⃣ Gestion des Clients

### 📋 Liste des clients (avec pagination)
```http
GET /api/clients?page=1&limit=10
Authorization: Bearer {token}
```

**Permissions**: `ROLE_ADMIN` ou `ROLE_SUPER_ADMIN`

**Paramètres Query:**
- `page` (optionnel, défaut: 1): Numéro de page
- `limit` (optionnel, défaut: 10): Nombre d'éléments par page (max: 100)

**Réponse 200 OK:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Entreprise ABC",
      "firstName": "Jean",
      "lastName": "Dupont",
      "email": "jean@example.com",
      "phone": "+33612345678",
      "adresse": "123 Rue de Paris",
      "profilePicturePath": "/uploads/clients/profile_1.jpg",
      "identityProofs": [
        {
          "id": 1,
          "identityType": "passport",
          "filePath": "/uploads/clients/identity_1.pdf",
          "downloadCount": 5
        }
      ],
      "createdAt": "2026-02-01T10:30:00+00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 45,
    "totalPages": 5
  }
}
```

**Exemple React avec hooks:**
```javascript
const ClientsList = () => {
  const [clients, setClients] = useState([]);
  const [pagination, setPagination] = useState({});
  const [page, setPage] = useState(1);
  
  useEffect(() => {
    const fetchClients = async () => {
      const token = localStorage.getItem('authToken');
      const response = await fetch(
        `http://localhost:8000/api/clients?page=${page}&limit=10`,
        {
          headers: { 'Authorization': `Bearer ${token}` }
        }
      );
      const data = await response.json();
      setClients(data.data);
      setPagination(data.pagination);
    };
    
    fetchClients();
  }, [page]);
  
  return (
    <div>
      {/* Afficher les clients */}
      {/* Afficher la pagination */}
    </div>
  );
};
```

---

### 🔍 Détail d'un client
```http
GET /api/clients/{id}
Authorization: Bearer {token}
```

**Permissions**: `ROLE_ADMIN` ou `ROLE_SUPER_ADMIN`

**Réponse 200 OK:** (même structure qu'un élément de la liste)

---

### ➕ Créer un client
```http
POST /api/clients
Authorization: Bearer {token}
Content-Type: multipart/form-data

name=Entreprise XYZ
firstName=Marie
lastName=Martin
email=marie@example.com
phone=+33612345679
adresse=456 Avenue des Champs
identityType=national_id
profilePicture=@photo.jpg
identityFile=@id_card.pdf
```

**Permissions**: `ROLE_SUPER_ADMIN` uniquement

**Champs requis:**
- `name`, `firstName`, `lastName`, `phone`, `email`, `adresse`

**Champs optionnels:**
- `profilePicture` (fichier image)
- `identityType` (enum: `passport`, `national_id`, `driver_license`, `voter_card`, `other`)
- `identityFile` (fichier PDF/image du document d'identité)

**Réponse 201 Created:** (structure client complète)

**Exemple JavaScript:**
```javascript
const createClient = async (clientData, profilePic, identityDoc) => {
  const token = localStorage.getItem('authToken');
  const formData = new FormData();
  
  // Champs texte
  Object.keys(clientData).forEach(key => {
    formData.append(key, clientData[key]);
  });
  
  // Fichiers
  if (profilePic) formData.append('profilePicture', profilePic);
  if (identityDoc) {
    formData.append('identityFile', identityDoc);
    formData.append('identityType', clientData.identityType || 'other');
  }
  
  const response = await fetch('http://localhost:8000/api/clients', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error);
  }
  
  return await response.json();
};
```

---

### 🔄 Mettre à jour un client
```http
PUT /api/clients/{id}
Authorization: Bearer {token}
Content-Type: multipart/form-data

name=Nouveau Nom
email=newemail@example.com
profilePicture=@new_photo.jpg
```

**Permissions**: `ROLE_SUPER_ADMIN` uniquement

**Tous les champs sont optionnels** - seuls les champs fournis seront mis à jour

**Réponse 200 OK:** (structure client complète)

---

### 🗑️ Supprimer un client
```http
DELETE /api/clients/{id}
Authorization: Bearer {token}
```

**Permissions**: `ROLE_SUPER_ADMIN` uniquement

**Réponse 200 OK:**
```json
{
  "message": "Client deleted"
}
```

---

### 📥 Télécharger un document d'identité
```http
GET /api/clients/{clientId}/identity-proofs/{proofId}/download
Authorization: Bearer {token}
```

**Permissions**: `ROLE_ADMIN` ou `ROLE_SUPER_ADMIN`

**Réponse 200 OK:** Fichier binaire (PDF ou image)

**Note importante:** Cette action incrémente automatiquement le compteur `downloadCount` du document

**Exemple JavaScript:**
```javascript
const downloadIdentityDocument = async (clientId, proofId) => {
  const token = localStorage.getItem('authToken');
  const response = await fetch(
    `http://localhost:8000/api/clients/${clientId}/identity-proofs/${proofId}/download`,
    {
      headers: { 'Authorization': `Bearer ${token}` }
    }
  );
  
  if (!response.ok) {
    throw new Error('Download failed');
  }
  
  // Télécharger le fichier
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `identity_proof_${proofId}.pdf`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(url);
};
```

---

## 4️⃣ Administration

**Note:** Tous les endpoints admin nécessitent `ROLE_ADMIN` ou `ROLE_SUPER_ADMIN`

### 📊 Statistiques - Total clients
```http
GET /api/admin/clients/total
Authorization: Bearer {token}
```

**Réponse 200 OK:**
```json
{
  "total": 145
}
```

---

### 📈 Statistiques - Croissance clients
```http
GET /api/admin/clients/growth
Authorization: Bearer {token}
```

**Réponse 200 OK:**
```json
{
  "growth": [
    { "month": "2026-01", "count": 12 },
    { "month": "2026-02", "count": 18 }
  ]
}
```

---

### 👥 Statistiques - Total utilisateurs
```http
GET /api/admin/users/total
Authorization: Bearer {token}
```

**Réponse 200 OK:**
```json
{
  "total": 25
}
```

---

### 🆕 Clients récents
```http
GET /api/admin/clients/recent?limit=10
Authorization: Bearer {token}
```

**Paramètres Query:**
- `limit` (optionnel, défaut: 10): Nombre de clients à retourner

**Réponse 200 OK:**
```json
{
  "clients": [
    {
      "id": 45,
      "name": "Dernier Client",
      "firstName": "Pierre",
      "lastName": "Durand",
      "createdAt": "2026-02-03 14:30:25"
    }
  ]
}
```

---

### 👤 Liste des utilisateurs
```http
GET /api/admin/users
Authorization: Bearer {token}
```

**Réponse 200 OK:**
```json
{
  "users": [
    {
      "id": 1,
      "email": "superadmin@example.com",
      "firstName": "Super",
      "lastName": "Admin",
      "roles": ["ROLE_SUPER_ADMIN", "ROLE_USER"],
      "profilePicturePath": null
    },
    {
      "id": 2,
      "email": "admin@example.com",
      "firstName": "Admin",
      "lastName": "User",
      "roles": ["ROLE_ADMIN", "ROLE_USER"],
      "profilePicturePath": "/uploads/profile/admin_pic.jpg"
    }
  ]
}
```

---

## 🔑 Hiérarchie des rôles

```
ROLE_SUPER_ADMIN (hérite de ROLE_ADMIN et ROLE_USER)
    ↓
ROLE_ADMIN (hérite de ROLE_USER)
    ↓
ROLE_USER (utilisateur de base)
```

**Résumé des permissions:**

| Endpoint | ROLE_USER | ROLE_ADMIN | ROLE_SUPER_ADMIN |
|----------|-----------|------------|------------------|
| Profil (GET/PUT) | ✅ | ✅ | ✅ |
| Clients (GET) | ❌ | ✅ | ✅ |
| Clients (POST/PUT/DELETE) | ❌ | ❌ | ✅ |
| Download documents | ❌ | ✅ | ✅ |
| Admin stats | ❌ | ✅ | ✅ |
| Register user | ❌ | ❌ | ✅ |

---

## 🛠️ Gestion des erreurs

### Codes de statut HTTP

- **200 OK**: Succès
- **201 Created**: Ressource créée
- **400 Bad Request**: Erreur de validation
- **401 Unauthorized**: Non authentifié (token manquant/invalide/expiré)
- **403 Forbidden**: Permissions insuffisantes
- **404 Not Found**: Ressource non trouvée

### Format des erreurs

```json
{
  "error": "Message d'erreur explicite"
}
```

### Exemple de gestion d'erreur en JavaScript

```javascript
const apiCall = async (url, options) => {
  const token = localStorage.getItem('authToken');
  
  const response = await fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': `Bearer ${token}`
    }
  });
  
  if (response.status === 401) {
    // Token expiré - rediriger vers login
    localStorage.removeItem('authToken');
    window.location.href = '/login';
    throw new Error('Session expirée');
  }
  
  if (response.status === 403) {
    throw new Error('Permissions insuffisantes');
  }
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error || 'Une erreur est survenue');
  }
  
  return await response.json();
};
```

---

## 📦 Exemple d'intégration complète (React)

```javascript
// services/api.js
const API_BASE = 'http://localhost:8000/api';

class ApiService {
  constructor() {
    this.token = localStorage.getItem('authToken');
  }
  
  setToken(token) {
    this.token = token;
    localStorage.setItem('authToken', token);
  }
  
  clearToken() {
    this.token = null;
    localStorage.removeItem('authToken');
  }
  
  async request(endpoint, options = {}) {
    const headers = {
      ...options.headers,
    };
    
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }
    
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
    }
    
    const response = await fetch(`${API_BASE}${endpoint}`, {
      ...options,
      headers
    });
    
    if (response.status === 401) {
      this.clearToken();
      throw new Error('Session expirée');
    }
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Erreur API');
    }
    
    return await response.json();
  }
  
  // Auth
  async login(email, password) {
    const data = await this.request('/login', {
      method: 'POST',
      body: JSON.stringify({ username: email, password })
    });
    this.setToken(data.token);
    return data;
  }
  
  // Profile
  async getProfile() {
    return await this.request('/profile');
  }
  
  async updateProfile(formData) {
    return await this.request('/profile', {
      method: 'PUT',
      body: formData
    });
  }
  
  // Clients
  async getClients(page = 1, limit = 10) {
    return await this.request(`/clients?page=${page}&limit=${limit}`);
  }
  
  async createClient(formData) {
    return await this.request('/clients', {
      method: 'POST',
      body: formData
    });
  }
  
  async getClient(id) {
    return await this.request(`/clients/${id}`);
  }
  
  // Admin
  async getUsers() {
    return await this.request('/admin/users');
  }
  
  async getClientStats() {
    const total = await this.request('/admin/clients/total');
    const growth = await this.request('/admin/clients/growth');
    const recent = await this.request('/admin/clients/recent?limit=5');
    return { total, growth, recent };
  }
}

export default new ApiService();
```

---

## 🧪 Comptes de test

```javascript
// Super Admin
{
  username: "superadmin@example.com",
  password: "superadminpass"
}

// Admin
{
  username: "admin@example.com",
  password: "adminpass"
}

// User
{
  username: "user@example.com",
  password: "userpass"
}
```

---

## 📝 Notes importantes

1. **Token JWT**: Le token expire après 1 heure. Gérez le rafraîchissement ou redirigez vers login
2. **CORS**: Configuré pour accepter toutes les origines en développement
3. **Upload de fichiers**: Utilisez `multipart/form-data` avec FormData
4. **Pagination**: Limite maximale de 100 éléments par page
5. **Download tracking**: Chaque téléchargement de document incrémente automatiquement le compteur
6. **Roles**: Les permissions sont gérées par hiérarchie (SUPER_ADMIN hérite des droits ADMIN et USER)

---

## 🔗 Documentation Swagger

Documentation interactive disponible sur: **http://localhost:8000/api/doc**

1. Cliquez sur **"Authorize"** (🔓) en haut à droite
2. Collez votre token JWT
3. Testez les endpoints directement depuis l'interface

---

**Version**: 1.0.0  
**Date**: 3 Février 2026  
**Contact**: dev@project-david.com
