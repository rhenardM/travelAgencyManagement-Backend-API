#!/bin/bash

# Script de test simplifié
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

API_URL="http://localhost:8000"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Tests API - Nouvelles Fonctionnalités${NC}"
echo -e "${BLUE}========================================${NC}\n"

# 1. Connexions
echo -e "${YELLOW}1. Connexion des utilisateurs...${NC}\n"

SUPER_ADMIN_TOKEN=$(curl -s -X POST "$API_URL/api/login" -H "Content-Type: application/json" \
  -d '{"username": "superadmin@example.com", "password": "superadminpass"}' | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "Super Admin: ${SUPER_ADMIN_TOKEN:0:30}..."

ADMIN_TOKEN=$(curl -s -X POST "$API_URL/api/login" -H "Content-Type: application/json" \
  -d '{"username": "admin@example.com", "password": "adminpass"}' | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "Admin: ${ADMIN_TOKEN:0:30}..."

USER_TOKEN=$(curl -s -X POST "$API_URL/api/login" -H "Content-Type: application/json" \
  -d '{"username": "user@example.com", "password": "userpass"}' | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "User: ${USER_TOKEN:0:30}...\n"

# 2. Test pagination
echo -e "${YELLOW}2. Test pagination...${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/clients" -H "Authorization: Bearer $SUPER_ADMIN_TOKEN")
echo -e "Liste clients (SUPER_ADMIN): ${HTTP_CODE} $([ $HTTP_CODE -eq 200 ] && echo -e "${GREEN}✅${NC}" || echo -e "${RED}❌${NC}")"

# 3. Test permissions
echo -e "\n${YELLOW}3. Test permissions...${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/clients" -H "Authorization: Bearer $ADMIN_TOKEN")
echo -e "Liste clients (ADMIN): ${HTTP_CODE} $([ $HTTP_CODE -eq 200 ] && echo -e "${GREEN}✅${NC}" || echo -e "${RED}❌${NC}")"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$API_URL/api/clients" -H "Authorization: Bearer $ADMIN_TOKEN" \
  -F "name=Test" -F "firstName=John" -F "lastName=Doe" -F "phone=+123" -F "email=t@t.com" -F "adresse=123 St")
echo -e "Créer client (ADMIN): ${HTTP_CODE} $([ $HTTP_CODE -eq 403 ] && echo -e "${GREEN}✅ Bloqué${NC}" || echo -e "${RED}❌ Devrait être 403${NC}")"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$API_URL/api/clients" -H "Authorization: Bearer $SUPER_ADMIN_TOKEN" \
  -F "name=TestClient" -F "firstName=Jane" -F "lastName=Smith" -F "phone=+9876543$(date +%s)" -F "email=jane$(date +%s)@test.com" -F "adresse=456 Ave")
echo -e "Créer client (SUPER_ADMIN): ${HTTP_CODE} $([ $HTTP_CODE -eq 201 ] && echo -e "${GREEN}✅${NC}" || echo -e "${RED}❌${NC}")"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/clients" -H "Authorization: Bearer $USER_TOKEN")
echo -e "Liste clients (USER): ${HTTP_CODE} $([ $HTTP_CODE -eq 403 ] && echo -e "${GREEN}✅ Bloqué${NC}" || echo -e "${RED}❌ Devrait être 403${NC}")"

# 4. Test profil
echo -e "\n${YELLOW}4. Test profil utilisateur...${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL/api/profile" -H "Authorization: Bearer $USER_TOKEN")
echo -e "Voir profil (USER): ${HTTP_CODE} $([ $HTTP_CODE -eq 200 ] && echo -e "${GREEN}✅${NC}" || echo -e "${RED}❌${NC}")"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$API_URL/api/profile" -H "Authorization: Bearer $USER_TOKEN" \
  -F "firstName=UpdatedName")
echo -e "Modifier profil (USER): ${HTTP_CODE} $([ $HTTP_CODE -eq 200 ] && echo -e "${GREEN}✅${NC}" || echo -e "${RED}❌${NC}")"

# 5. Résumé
echo -e "\n${BLUE}========================================${NC}"
echo -e "${GREEN}✅ Tests terminés !${NC}"
echo -e "${BLUE}========================================${NC}\n"
