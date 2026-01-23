<?php

namespace App\Service;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ClientService
{
    private EntityManagerInterface $em;
    private string $uploadDir;

public function __construct(EntityManagerInterface $em, string $uploadDir)
{
    $this->em = $em;
    $this->uploadDir = $uploadDir; // Symfony injectera le paramètre
}



    /**
     * Crée un client et upload son document
     */
    public function createClient(array $data, ?UploadedFile $file = null): Client
    {
        $client = new Client();
        $client->setName($data['name']);
        $client->setFirstName($data['firstName']);
        $client->setLastName($data['lastName'] ?? '');
        $client->setNumber($data['number']);
        $client->setEmail($data['email'] ?? null);
        $client->setAdresse($data['adresse'] ?? '');

        // Gestion upload fichier
        if ($file) {
            $filename = uniqid('client_') . '.' . $file->guessExtension();
            $file->move($this->uploadDir, $filename);
            $client->setIdentityDocumentPath('/uploads/clients/' . $filename);
        }

        $this->em->persist($client);
        $this->em->flush();

        return $client;
    }

    /**
     * Récupère tous les clients
     */
    public function getAllClients(): array
    {
        return $this->em->getRepository(Client::class)->findAll();
    }

    /**
     * Récupère un client par ID
     */
    public function getClientById(int $id): ?Client
    {
        return $this->em->getRepository(Client::class)->find($id);
    }

    /**
     * Supprime un client
     */
    public function deleteClient(Client $client): void
    {
        $this->em->remove($client);
        $this->em->flush();
    }
}
