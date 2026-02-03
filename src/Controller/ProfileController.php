<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private string $uploadDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        string $uploadDir
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->uploadDir = $uploadDir;
    }

    // GET /api/profile
    #[Route('', name: 'profile_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/profile',
        summary: 'Afficher le profil de l\'utilisateur connecté',
        security: [['bearerAuth' => []]],
        tags: ['Profil'],
        responses: [
            new OA\Response(response: 200, description: 'Profil utilisateur'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'profilePicturePath' => $user->getProfilePicturePath()
        ]);
    }

    // PUT /api/profile
    #[Route('', name: 'profile_update', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: '/api/profile',
        summary: 'Mettre à jour le profil de l\'utilisateur',
        security: [['bearerAuth' => []]],
        tags: ['Profil'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'firstName', type: 'string', description: 'Prénom'),
                        new OA\Property(property: 'lastName', type: 'string', description: 'Nom de famille'),
                        new OA\Property(property: 'email', type: 'string', description: 'Email'),
                        new OA\Property(property: 'profilePicture', type: 'string', format: 'binary', description: 'Photo de profil')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profil mis à jour'),
            new OA\Response(response: 400, description: 'Erreur de validation'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function update(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $data = $request->request->all();
        $profilePicture = $request->files->get('profilePicture');

        // Mise à jour des informations de base
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['email'])) {
            // Vérifier que l'email n'est pas déjà utilisé
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json(['error' => 'Cet email est déjà utilisé'], Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail($data['email']);
        }

        // Upload de la photo de profil
        if ($profilePicture && $profilePicture->getSize() > 0) {
            $allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            $mimeType = $profilePicture->getMimeType();
            if ($mimeType && !in_array($mimeType, $allowedMimeTypes, true)) {
                return $this->json(
                    ['error' => 'Seules les images (jpg, png, gif, webp, avif) sont acceptées'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            // Supprimer l'ancienne photo si elle existe
            if ($user->getProfilePicturePath()) {
                $oldFile = $this->uploadDir . '/' . basename($user->getProfilePicturePath());
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            $filename = uniqid('user_profile_') . '.' . $profilePicture->guessExtension();
            $profilePicture->move($this->uploadDir, $filename);
            $user->setProfilePicturePath('/uploads/' . $filename);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'profile' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'profilePicturePath' => $user->getProfilePicturePath()
            ]
        ]);
    }
}
