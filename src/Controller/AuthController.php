<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

use OpenApi\Attributes as OA;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/me', name: 'user_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Infos du user connecté',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Infos du user', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'firstName', type: 'string'),
                    new OA\Property(property: 'lastName', type: 'string'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function me(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Cast $user to App\Entity\User to access custom methods
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Utilisateur non valide'], Response::HTTP_UNAUTHORIZED);
        }

        // Retourne les infos de base du user (adapte selon les propriétés à exposer)
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/register', name: 'user_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscription d\'un utilisateur',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email', 'password'],
                    properties: [
                        new OA\Property(property: 'firstName', type: 'string'),
                        new OA\Property(property: 'lastName', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'password', type: 'string'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Utilisateur créé'),
            new OA\Response(response: 400, description: 'Erreur de validation')
        ]
    )]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }
        $roles = $data['roles'] ?? ['ROLE_USER'];
        $user = new User();
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setEmail($data['email']);
        $user->setRoles($roles);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $em->persist($user);
        $em->flush();
        return $this->json(['message' => 'User registered'], Response::HTTP_CREATED);
    }

    /**
     * Connexion (endpoint appelé après l'authentification JWT)
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Cette méthode ne sera jamais appelée car la connexion est gérée par JWT
        // Elle sert uniquement de point d'entrée pour la configuration de sécurité et la documentation
        return new JsonResponse([
            'success' => false,
            'message' => 'Cette route ne devrait pas être appelée directement'
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Déconnexion (informationnel - JWT est stateless)
     */
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/logout',
        summary: 'Déconnexion (stateless)',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Déconnexion réussie')
        ]
    )]
    public function logout(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Déconnexion réussie. Supprimez le token côté client.'
        ]);
    }
}
