<?php


namespace App\Controller;

use App\Entity\IdentityProof;
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
    private string $uploadDir;

    public function __construct(
        ClientService $clientService, 
        SerializerInterface $serializer, 
        EntityManagerInterface $entityManager,
        string $uploadDir
    )
    {
        $this->clientService = $clientService;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->uploadDir = $uploadDir;
    }

    // GET /api/clients
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'client_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients',
        summary: 'Liste tous les clients avec pagination',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Numéro de page'),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10), description: 'Nombre d\'éléments par page')
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des clients avec pagination'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function list(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 10)));
        
        $result = $this->clientService->getAllClients($page, $limit);
        $json = $this->serializer->serialize($result['data'], 'json', ['groups' => ['client']]);
        
        return $this->json([
            'data' => json_decode($json),
            'pagination' => $result['pagination']
        ]);
    }

    // GET /api/clients/{id}
    #[IsGranted('ROLE_ADMIN')]
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
    #[Route('', name: 'client_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/clients',
        summary: 'Créer un client',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'firstName', 'lastName', 'phone', 'email', 'adresse'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', description: 'Nom du client'),
                        new OA\Property(property: 'firstName', type: 'string', description: 'Prénom'),
                        new OA\Property(property: 'lastName', type: 'string', description: 'Nom de famille'),
                        new OA\Property(property: 'phone', type: 'string', description: 'Numéro de téléphone'),
                        new OA\Property(property: 'email', type: 'string', description: 'Email'),
                        new OA\Property(property: 'adresse', type: 'string', description: 'Adresse'),
                        new OA\Property(property: 'profilePicture', type: 'string', format: 'binary', description: 'Photo de profil (optionnel)'),
                        new OA\Property(
                            property: 'identityType', 
                            type: 'string', 
                            description: 'Type de document (passport, national_id, driver_license, voter_card, other)',
                            enum: ['passport', 'national_id', 'driver_license', 'voter_card', 'other']
                        ),
                        new OA\Property(property: 'identityFile', type: 'string', format: 'binary', description: 'Document d\'identité (optionnel)')
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
        $profilePicture = $request->files->get('profilePicture');
        $identityFile = $request->files->get('identityFile');

        try {
            $client = $this->clientService->createClient($data, $profilePicture, $identityFile);
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
                        new OA\Property(property: 'name', type: 'string', description: 'Nom du client'),
                        new OA\Property(property: 'firstName', type: 'string', description: 'Prénom'),
                        new OA\Property(property: 'lastName', type: 'string', description: 'Nom de famille'),
                        new OA\Property(property: 'phone', type: 'string', description: 'Numéro de téléphone'),
                        new OA\Property(property: 'email', type: 'string', description: 'Email'),
                        new OA\Property(property: 'adresse', type: 'string', description: 'Adresse'),
                        new OA\Property(property: 'profilePicture', type: 'string', format: 'binary', description: 'Nouvelle photo de profil (optionnel)'),
                        new OA\Property(
                            property: 'identityType', 
                            type: 'string', 
                            description: 'Type de document (optionnel)',
                            enum: ['passport', 'national_id', 'driver_license', 'voter_card', 'other']
                        ),
                        new OA\Property(property: 'identityFile', type: 'string', format: 'binary', description: 'Nouveau document d\'identité (optionnel)')
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
        $profilePicture = $request->files->get('profilePicture');
        $identityFile = $request->files->get('identityFile');

        try {
            $this->clientService->updateClient($client, $data, $profilePicture, $identityFile);
            $json = $this->serializer->serialize($client, 'json', ['groups' => ['client']]);
            return $this->json(json_decode($json));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
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

    // GET /api/clients/{clientId}/identity-proofs/{proofId}/download
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{clientId}/identity-proofs/{proofId}/download', name: 'client_identity_download', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients/{clientId}/identity-proofs/{proofId}/download',
        summary: 'Télécharger un document d\'identité',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(name: 'clientId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'proofId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Document téléchargé'),
            new OA\Response(response: 404, description: 'Document non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function downloadIdentityProof(int $clientId, int $proofId): Response
    {
        $client = $this->clientService->getClientById($clientId);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        $identityProof = $this->entityManager->getRepository(IdentityProof::class)->find($proofId);
        if (!$identityProof || $identityProof->getClient()->getId() !== $clientId) {
            return $this->json(['error' => 'Identity proof not found'], Response::HTTP_NOT_FOUND);
        }

        // Incrémenter le compteur de téléchargements
        $identityProof->incrementDownloadCount();
        $this->entityManager->flush();

        // Retourner le fichier
        $filePath = $this->uploadDir . '/' . basename($identityProof->getFilePath());
        if (!file_exists($filePath)) {
            return $this->json(['error' => 'File not found on server'], Response::HTTP_NOT_FOUND);
        }

        return $this->file($filePath);
    }
}
