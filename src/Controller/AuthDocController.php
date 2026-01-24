<?php
// Ce contrôleur expose déjà /api/register
// Pour la connexion JWT, l’URL est gérée par LexikJWTAuthenticationBundle
// Il suffit d’utiliser POST /api/login_check avec { username, password }
//
// Si besoin d’un endpoint personnalisé, on peut le créer, mais LexikJWT est standard et sécurisé

namespace App\Controller;

use OpenApi\Attributes as OA;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthDocController extends AbstractController
{
    // (Supprimé : endpoint /api/login_check pour laisser LexikJWT gérer l'authentification)
}
