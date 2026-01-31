# Documentation d'intégration API (Front-end)

## Authentification (Connexion)

### Endpoint : Connexion utilisateur
- **URL** : `/api/login_check`
- **Méthode** : `POST`
- **Headers** :
  - `Content-Type: application/json`
- **Body (JSON)** :
```json
{
  "username": "email@exemple.com",
  "password": "motdepasse"
}
```
- **Réponse (succès)** :
```json
{
  "token": "<JWT>"
}
```
- **À faire côté front** :
  - Stocker le token JWT (localStorage, sessionStorage, etc).
  - Ajouter le header `Authorization: Bearer <token>` pour toutes les requêtes protégées.

---

## Connexion d'un utilisateur 

- **URL** : `/api/login/`
- **Méthode** : `POST`
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer <token>`
- **Body (JSON)** :
```json
{
  "email": "nouveau@exemple.com",
  "password": "motdepasse",
}
```

## Déconnexion 
- **URL** : `/api/logout/`

## Création d'un utilisateur

### Endpoint : Créer un utilisateur
- **URL** : `/api/users/`
- **Méthode** : `POST`
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer <token>`
- **Body (JSON)** :
```json
{
   "fristName" : "Rhenard",
   "lastName": "Munongo",
  "email": "nouveau@exemple.com",
  "password": "motdepasse",
  "roles": ["ROLE_USER"]
}
```
- **Réponse (succès)** :
```json
{
  "id": 1,
  "email": "nouveau@exemple.com",
  ...
}
```

---

## CRUD Client

### 1. Lister les clients
- **URL** : `/api/clients/`
- **Méthode** : `GET`
- **Headers** :
  - `Authorization: Bearer <token>`
- **Réponse** :
  - Liste d'objets client

### 2. Détail d'un client
- **URL** : `/api/clients/{id}`
- **Méthode** : `GET`
- **Headers** :
  - `Authorization: Bearer <token>`
- **Réponse** :
  - Objet client détaillé

### 3. Créer un client
- **URL** : `/api/clients/`
- **Méthode** : `POST`
- **Headers** :
  - `Authorization: Bearer <token>`
  - `Content-Type: multipart/form-data`
- **Body (form-data)** :
  - `name` (string)
  - `firstName` (string)
  - `lastName` (string)
  - `phone` (string)
  - `email` (string)
  - `adresse` (string)
  - `identityType` (string)
  - `profilePicture` (fichier image, optionnel)
  - `identityFile` (fichier image/pdf, obligatoire)
- **Réponse** :
  - Objet client créé

### 4. Modifier un client
- **URL** : `/api/clients/{id}`
- **Méthode** : `PUT`
- **Headers** :
  - `Authorization: Bearer <token>`
  - `Content-Type: multipart/form-data`
- **Body (form-data)** :
  - Champs à modifier (voir création)
- **Réponse** :
  - Objet client mis à jour

### 5. Supprimer un client
- **URL** : `/api/clients/{id}`
- **Méthode** : `DELETE`
- **Headers** :
  - `Authorization: Bearer <token>`
- **Réponse** :
  - Message de confirmation

---

## Remarques générales
- Toujours envoyer le token JWT dans le header `Authorization` pour les endpoints protégés.
- Pour les uploads de fichiers, utiliser `multipart/form-data`.
- Les réponses d'erreur sont généralement au format : `{ "error": "message" }`.

---

## Exemple d'appel avec fetch (JS)

```js
// Connexion
fetch('/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: 'email@exemple.com', password: 'motdepasse' })
})
  .then(res => res.json())
  .then(data => localStorage.setItem('token', data.token));

// Lister les clients
fetch('/api/clients/', {
  headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
})
  .then(res => res.json())
  .then(console.log);
```
