<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    private ClientRepository $clientRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;

    public function __construct(ClientRepository $clientRepository, UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
        $this->em = $em;
    }
    #[Route('/clients/total', name: 'admin_clients_total', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/clients/total',
        summary: 'Total des clients',
        tags: ['Admin'],
        responses: [
            new OA\Response(response: 200, description: 'Nombre total de clients')
        ]
    )]
    public function totalClients(): Response
    {
        $total = $this->clientRepository->count([]);
        return $this->json(['total' => $total]);
    }

    #[Route('/clients/growth', name: 'admin_clients_growth', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/clients/growth',
        summary: 'Croissance des clients par mois',
        tags: ['Admin'],
        responses: [
            new OA\Response(response: 200, description: 'Croissance mensuelle', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'growth', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'month', type: 'string'),
                            new OA\Property(property: 'total', type: 'integer')
                        ]
                    ))
                ]
            ))
        ]
    )]
    public function clientsGrowth(): Response
    {
        $conn = $this->em->getConnection();
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total FROM client GROUP BY month ORDER BY month ASC";
        $result = $conn->executeQuery($sql)->fetchAllAssociative();
        return $this->json(['growth' => $result]);
    }

    #[Route('/users/total', name: 'admin_users_total', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/users/total',
        summary: 'Total des utilisateurs',
        tags: ['Admin'],
        responses: [
            new OA\Response(response: 200, description: 'Nombre total d\'utilisateurs', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'total', type: 'integer')
                ]
            ))
        ]
    )]
    public function totalUsers(): Response
    {
        $total = $this->userRepository->count([]);
        return $this->json(['total' => $total]);
    }

    #[Route('/clients/recent', name: 'admin_clients_recent', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/clients/recent',
        summary: 'Derniers clients créés',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Nombre de clients à retourner (défaut: 10)')
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des clients récents', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'clients', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'firstName', type: 'string'),
                            new OA\Property(property: 'lastName', type: 'string'),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                        ]
                    ))
                ]
            ))
        ]
    )]
    public function recentClients(Request $request): Response
    {
        $limit = (int) $request->query->get('limit', 10);
        $clients = $this->clientRepository->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $data = array_map(function($client) {
            return [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'firstName' => $client->getFirstName(),
                'lastName' => $client->getLastName(),
                'createdAt' => $client->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $clients);

        return $this->json(['clients' => $data]);
    }

    #[Route('/users', name: 'admin_users_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/users',
        summary: 'Liste de tous les utilisateurs',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste des utilisateurs', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'users', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'email', type: 'string'),
                            new OA\Property(property: 'firstName', type: 'string'),
                            new OA\Property(property: 'lastName', type: 'string'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                            new OA\Property(property: 'profilePicturePath', type: 'string')
                        ]
                    ))
                ]
            )),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function listUsers(): Response
    {
        $users = $this->userRepository->findAll();
        
        $data = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'profilePicturePath' => $user->getProfilePicturePath()
            ];
        }, $users);

        return $this->json(['users' => $data]);
    }
}
