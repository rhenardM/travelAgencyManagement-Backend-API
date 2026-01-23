<?php

namespace App\Controller;

use App\Entity\Client;
use App\Service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/clients')]
class ClientController extends AbstractController
{
    private ClientService $clientService;
    private SerializerInterface $serializer;

    public function __construct(ClientService $clientService, SerializerInterface $serializer)
    {
        $this->clientService = $clientService;
        $this->serializer = $serializer;
    }

    // GET /api/clients
    #[Route('', name: 'client_list', methods: ['GET'])]
    public function list(): Response
    {
        $clients = $this->clientService->getAllClients();
        $json = $this->serializer->serialize($clients, 'json', ['groups' => ['client']]);
        return $this->json(json_decode($json));
    }

    // GET /api/clients/{id}
    #[Route('/{id}', name: 'client_show', methods: ['GET'])]
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
    #[Route('', name: 'client_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = $request->request->all();
        $file = $request->files->get('identityDocument');

        $client = $this->clientService->createClient($data, $file);

        $json = $this->serializer->serialize($client, 'json', ['groups' => ['client']]);
        return $this->json(json_decode($json), Response::HTTP_CREATED);
    }

    // DELETE /api/clients/{id}
    #[Route('/{id}', name: 'client_delete', methods: ['DELETE'])]
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
