<?php


namespace App\Controller;

use App\Entity\Client;
use App\Service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use OpenApi\Attributes as OA;


#[Route('/api/clients')]
class ClientController extends AbstractController
{
    private ClientService $clientService;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;

    public function __construct(ClientService $clientService, SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->clientService = $clientService;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    // GET /api/clients
    #[IsGranted('ROLE_ADMIN or ROLE_SUPER_ADMIN')]
    #[Route('/', name: 'client_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients/',
        summary: 'Liste tous les clients',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des clients'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function list(): Response
    {
        $clients = $this->clientService->getAllClients();
        $json = $this->serializer->serialize($clients, 'json', ['groups' => ['client']]);
        return $this->json(json_decode($json));
    }

    // GET /api/clients/{id}
    #[IsGranted('ROLE_ADMIN or ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'client_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients/{id}',
        summary: 'Détail d\'un client',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail du client'),
            new OA\Response(response: 404, description: 'Client non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function show(int $id): Response
    {
        $client = $this->clientService->getClientById($id);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($client, 'json', ['groups' => ['client']]);
        return $this->json(json_decode($json));
    }

    // POST /api/clients
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/', name: 'client_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/clients/',
        summary: 'Créer un client',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'firstName', 'lastName', 'number', 'email', 'adresse', 'identityDocument'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'firstName', type: 'string'),
                        new OA\Property(property: 'lastName', type: 'string'),
                        new OA\Property(property: 'number', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'adresse', type: 'string'),
                        new OA\Property(property: 'identityDocument', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Client créé'),
            new OA\Response(response: 400, description: 'Erreur de validation'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function create(Request $request): Response
    {
        $data = $request->request->all();
        $file = $request->files->get('identityDocument');

        try {
            $client = $this->clientService->createClient($data, $file);
            $json = $this->serializer->serialize($client, 'json', ['groups' => ['client']]);
            return $this->json(json_decode($json), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // PUT /api/clients/{id}
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'client_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/clients/{id}',
        summary: 'Met à jour un client',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'firstName', type: 'string'),
                        new OA\Property(property: 'lastName', type: 'string'),
                        new OA\Property(property: 'number', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'adresse', type: 'string'),
                        new OA\Property(property: 'identityDocument', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Client mis à jour'),
            new OA\Response(response: 400, description: 'Erreur de validation'),
            new OA\Response(response: 404, description: 'Client non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function update(Request $request, int $id): Response
    {
        $client = $this->clientService->getClientById($id);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->request->all();
        $file = $request->files->get('identityDocument');

        // Met à jour les champs si présents dans la requête
        if (isset($data['name'])) $client->setName($data['name']);
        if (isset($data['firstName'])) $client->setFirstName($data['firstName']);
        if (isset($data['lastName'])) $client->setLastName($data['lastName']);
        if (isset($data['number'])) $client->setNumber($data['number']);
        if (isset($data['email'])) $client->setEmail($data['email']);
        if (isset($data['adresse'])) $client->setAdresse($data['adresse']);

        // Gestion du fichier identité (optionnel)
        if ($file) {
            $allowedMimeTypes = [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
                return $this->json(['error' => 'Seuls les fichiers PDF ou images (jpg, png, gif, webp, avif) sont acceptés.'], Response::HTTP_BAD_REQUEST);
            }
            $filename = uniqid('client_') . '.' . $file->guessExtension();
            $file->move($this->getParameter('upload_directory'), $filename);
            $client->setIdentityDocumentPath('/uploads/clients/' . $filename);
        }

        $this->entityManager->flush();

        $json = $this->serializer->serialize($client, 'json', ['groups' => ['client']]);
        return $this->json(json_decode($json));
    }

    // DELETE /api/clients/{id}
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'client_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/clients/{id}',
        summary: 'Supprime un client',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Client supprimé'),
            new OA\Response(response: 404, description: 'Client non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function delete(int $id): Response
    {
        $client = $this->clientService->getClientById($id);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        $this->clientService->deleteClient($client);
        return $this->json(['message' => 'Client deleted']);
    }
}
