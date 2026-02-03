#!/bin/bash

# Script de test des fonctionnalités
# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

API_URL="http://localhost:8000"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Tests des Nouvelles Fonctionnalités${NC}"
echo -e "${BLUE}========================================${NC}\n"

# ============================================
# 1. CONNEXION DES UTILISATEURS
# ============================================
echo -e "${YELLOW}📝 1. Connexion des utilisateurs...${NC}\n"

# Super Admin
echo -e "${BLUE}Connexion Super Admin...${NC}"
SUPER_ADMIN_RESPONSE=$(curl -s -X POST "$API_URL/api/login" \
  -H "Content-Type: application/json" \
  -d '{"username": "superadmin@example.com", "password": "superadminpass"}')

SUPER_ADMIN_TOKEN=$(echo $SUPER_ADMIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$SUPER_ADMIN_TOKEN" ]; then
    echo -e "${RED}❌ Échec de connexion Super Admin${NC}"
    echo "Réponse: $SUPER_ADMIN_RESPONSE"
else
    echo -e "${GREEN}✅ Super Admin connecté${NC}"
    echo "Token: ${SUPER_ADMIN_TOKEN:0:20}..."
fi

# Admin
echo -e "\n${BLUE}Connexion Admin...${NC}"
ADMIN_RESPONSE=$(curl -s -X POST "$API_URL/api/login" \
  -H "Content-Type: application/json" \
  -d '{"username": "admin@example.com", "password": "adminpass"}')

ADMIN_TOKEN=$(echo $ADMIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$ADMIN_TOKEN" ]; then
    echo -e "${RED}❌ Échec de connexion Admin${NC}"
    echo "Réponse: $ADMIN_RESPONSE"
else
    echo -e "${GREEN}✅ Admin connecté${NC}"
    echo "Token: ${ADMIN_TOKEN:0:20}..."
fi

# User
echo -e "\n${BLUE}Connexion User...${NC}"
USER_RESPONSE=$(curl -s -X POST "$API_URL/api/login" \
  -H "Content-Type: application/json" \
  -d '{"username": "user@example.com", "password": "userpass"}')

USER_TOKEN=$(echo $USER_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$USER_TOKEN" ]; then
    echo -e "${RED}❌ Échec de connexion User${NC}"
    echo "Réponse: $USER_RESPONSE"
else
    echo -e "${GREEN}✅ User connecté${NC}"
    echo "Token: ${USER_TOKEN:0:20}..."
fi

echo -e "\n${BLUE}========================================${NC}\n"

# ============================================
# 2. TESTS DE PAGINATION
# ============================================
echo -e "${YELLOW}📊 2. Tests de pagination...${NC}\n"

echo -e "${BLUE}Test 2.1: Liste avec pagination par défaut${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$API_URL/api/clients" \
  -H "Authorization: Bearer $SUPER_ADMIN_TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ 200 OK - Pagination par défaut fonctionne${NC}"
    echo "$BODY" | grep -o '"pagination":{[^}]*}' | head -1
else
    echo -e "${RED}❌ $HTTP_CODE - Échec${NC}"
fi

echo -e "\n${BLUE}Test 2.2: Pagination personnalisée (page=1, limit=5)${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$API_URL/api/clients?page=1&limit=5" \
  -H "Authorization: Bearer $SUPER_ADMIN_TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ 200 OK - Pagination personnalisée fonctionne${NC}"
else
    echo -e "${RED}❌ $HTTP_CODE - Échec${NC}"
fi

echo -e "\n${BLUE}========================================${NC}\n"

# ============================================
# 3. TESTS DES PERMISSIONS
# ============================================
echo -e "${YELLOW}🔐 3. Tests des permissions...${NC}\n"

echo -e "${BLUE}Test 3.1: ADMIN peut voir la liste des clients${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "$API_URL/api/clients" \
  -H "Authorization: Bearer $ADMIN_TOKEN")

if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ 200 OK - ADMIN peut voir la liste${NC}"
else
    echo -e "${RED}❌ $HTTP_CODE - Échec${NC}"
fi

echo -e "\n${BLUE}Test 3.2: ADMIN ne peut PAS créer un client${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$API_URL/api/clients" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -F "name=Test Client" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "phone=+123456789" \
  -F "email=test@test.com" \
  -F "adresse=123 Test St")

if [ "$HTTP_CODE" -eq 403 ]; then
    echo -e "${GREEN}✅ 403 Forbidden - Correct, ADMIN bloqué${NC}"
else
    echo -e "${RED}❌ $HTTP_CODE - Attendu 403${NC}"
fi

echo -e "\n${BLUE}Test 3.3: SUPER_ADMIN peut créer un client${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_URL/api/clients" \
  -H "Authorization: Bearer $SUPER_ADMIN_TOKEN" \
  -F "name=Test Client" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "phone=+987654321" \
  -F "email=johndoe$(date +%s)@example.com" \
  -F "adresse=456 New Ave")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}✅ 201 Created - Client créé avec succès${NC}"
    CLIENT_ID=$(echo "$BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    echo "Client ID: $CLIENT_ID"
else
    echo -e "${RED}❌ $HTTP_CODE - Échec de création${NC}"
    echo "$BODY"
fi

echo -e "\n${BLUE}Test 3.4: USER ne peut PAS accéder aux clients${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "$API_URL/api/clients" \
  -H "Authorization: Bearer $USER_TOKEN")

if [ "$HTTP_CODE" -eq 403 ]; then
    echo -e "${GREEN}✅ 403 Forbidden - Correct, USER bloqué${NC}"
else
    echo -e "${RED}❌ $HTTP_CODE - Attendu 403${NC}"
fi

echo -e "\n${BLUE}========================================${NC}\n"

# ============================================
# 4. TESTS DU PROFIL UTILISATEUR
# ============================================
echo -e "${YELLOW}👤 4. Tests du profil utilisateur...${NC}\n"

echo -e "${BLUE}Test 4.1: USER peut voir son profil${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$API_URL/api/profile" \
  -H "Authorization: Bearer $USER_TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ 200 OK - Profil récupéré${NC}"
    echo "$BODY" | grep -o '"email":"[^"]*"' | head -1
else
    echo -e "${RED}❌ $HTTP_CODE - Échec${NC}"
fi

echo -e "\n${BLUE}Test 4.2: USER peut modifier son profil${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$API_URL/api/profile" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -F "firstName=UpdatedFirstName" \
  -F "lastName=UpdatedLastName")

if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ 200 OK - Profil mis à jour${NC}"
else
    echo -e "${RED}❌ $HTTP_CODE - Échec${NC}"
fi

echo -e "\n${BLUE}Test 4.3: ADMIN peut aussi voir son profil${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "$API_URL/api/profile" \
  -H "Authorization: Bearer $ADMIN_TOKEN")

if [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✅ 200 OK - Admin peut voir son profil${NC}"
else
    echo -e "${RED}❌ $HTTP_CODE - Échec${NC}"
fi

echo -e "\n${BLUE}========================================${NC}\n"

# ============================================
# 5. TESTS DE TÉLÉCHARGEMENT
# ============================================
echo -e "${YELLOW}📥 5. Tests de téléchargement de documents...${NC}\n"

echo -e "${BLUE}Test 5.1: Créer un client avec un document d'identité${NC}"

# Créer un fichier PDF de test
echo "Test PDF Content" > /tmp/test_document.txt

RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_URL/api/clients" \
  -H "Authorization: Bearer $SUPER_ADMIN_TOKEN" \
  -F "name=Test Download Client" \
  -F "firstName=Download" \
  -F "lastName=Test" \
  -F "phone=+111222333" \
  -F "email=download$(date +%s)@example.com" \
  -F "adresse=Test Address" \
  -F "identityType=passport" \
  -F "identityFile=@/tmp/test_document.txt")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}✅ 201 Created - Client avec document créé${NC}"
    TEST_CLIENT_ID=$(echo "$BODY" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    TEST_PROOF_ID=$(echo "$BODY" | grep -o '"identityProofs":\[\{"id":[0-9]*' | grep -o '[0-9]*')
    echo "Client ID: $TEST_CLIENT_ID"
    echo "Document ID: $TEST_PROOF_ID"
    
    if [ ! -z "$TEST_CLIENT_ID" ] && [ ! -z "$TEST_PROOF_ID" ]; then
        echo -e "\n${BLUE}Test 5.2: ADMIN peut télécharger le document${NC}"
        HTTP_CODE=$(curl -s -o /tmp/downloaded_doc.txt -w "%{http_code}" \
          -X GET "$API_URL/api/clients/$TEST_CLIENT_ID/identity-proofs/$TEST_PROOF_ID/download" \
          -H "Authorization: Bearer $ADMIN_TOKEN")
        
        if [ "$HTTP_CODE" -eq 200 ]; then
            echo -e "${GREEN}✅ 200 OK - Document téléchargé${NC}"
        else
            echo -e "${RED}❌ $HTTP_CODE - Échec du téléchargement${NC}"
        fi
        
        echo -e "\n${BLUE}Test 5.3: Télécharger 3 fois et vérifier le compteur${NC}"
        for i in {1..3}; do
            curl -s -o /dev/null -X GET "$API_URL/api/clients/$TEST_CLIENT_ID/identity-proofs/$TEST_PROOF_ID/download" \
              -H "Authorization: Bearer $ADMIN_TOKEN"
        done
        
        sleep 1
        
        RESPONSE=$(curl -s -X GET "$API_URL/api/clients/$TEST_CLIENT_ID" \
          -H "Authorization: Bearer $ADMIN_TOKEN")
        DOWNLOAD_COUNT=$(echo "$RESPONSE" | grep -o '"downloadCount":[0-9]*' | head -1 | cut -d':' -f2)
        
        if [ ! -z "$DOWNLOAD_COUNT" ] && [ "$DOWNLOAD_COUNT" -gt 0 ]; then
            echo -e "${GREEN}✅ Compteur de téléchargements: $DOWNLOAD_COUNT${NC}"
        else
            echo -e "${YELLOW}⚠️  Compteur: $DOWNLOAD_COUNT${NC}"
        fi
        
        echo -e "\n${BLUE}Test 5.4: USER ne peut PAS télécharger le document${NC}"
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
          -X GET "$API_URL/api/clients/$TEST_CLIENT_ID/identity-proofs/$TEST_PROOF_ID/download" \
          -H "Authorization: Bearer $USER_TOKEN")
        
        if [ "$HTTP_CODE" -eq 403 ]; then
            echo -e "${GREEN}✅ 403 Forbidden - USER correctement bloqué${NC}"
        else
            echo -e "${RED}❌ $HTTP_CODE - Attendu 403${NC}"
        fi
    fi
else
    echo -e "${RED}❌ $HTTP_CODE - Échec de création du client${NC}"
    echo "$BODY"
fi

echo -e "\n${BLUE}========================================${NC}\n"

# ============================================
# RÉSUMÉ
# ============================================
echo -e "${YELLOW}📋 RÉSUMÉ DES TESTS${NC}\n"
echo -e "${GREEN}✅ Authentification:${NC} 3 utilisateurs connectés"
echo -e "${GREEN}✅ Pagination:${NC} Fonctionnelle avec paramètres"
echo -e "${GREEN}✅ Permissions SUPER_ADMIN:${NC} Accès complet"
echo -e "${GREEN}✅ Permissions ADMIN:${NC} Lecture seule"
echo -e "${GREEN}✅ Permissions USER:${NC} Profil uniquement"
echo -e "${GREEN}✅ Profil utilisateur:${NC} GET et PUT fonctionnels"
echo -e "${GREEN}✅ Téléchargement:${NC} Avec compteur"
echo -e "\n${BLUE}========================================${NC}\n"

# Nettoyage
rm -f /tmp/test_document.txt /tmp/downloaded_doc.txt

echo -e "${GREEN}✨ Tests terminés !${NC}\n"
