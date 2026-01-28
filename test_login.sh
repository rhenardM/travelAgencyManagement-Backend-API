# Test login superadmin
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "superadmin@example.com", "password": "superadminpass"}'

# Test login admin
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin@example.com", "password": "adminpass"}'

# Après avoir récupéré le token JWT, teste une route protégée (exemple)
# Remplace <TOKEN> par le token reçu :
curl -X GET http://localhost/api/clients/ \
  -H "Authorization: Bearer <TOKEN>"
