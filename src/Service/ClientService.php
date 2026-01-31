<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\IdentityProof;
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
    public function createClient(array $data, ?UploadedFile $profilePicture = null, ?UploadedFile $identityFile = null): Client
    {
        $client = new Client();
        $client->setName($data['name']);
        $client->setFirstName($data['firstName']);
        $client->setLastName($data['lastName'] ?? '');
        $client->setPhone($data['phone'] ?? '');
        $client->setEmail($data['email'] ?? null);
        $client->setAdresse($data['adresse'] ?? '');
        $client->setProfilePicturePath($profilePicture);
        $client->setCreatedAt(new \DateTimeImmutable());

        // Upload photo de profil
        if ($profilePicture) {
            $allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            if (!in_array($profilePicture->getMimeType(), $allowedMimeTypes, true)) {
                throw new \InvalidArgumentException('Seules les images (jpg, png, gif, webp, avif) sont acceptées pour la photo de profil.');
            }
            $filename = uniqid('profile_') . '.' . $profilePicture->guessExtension();
            $profilePicture->move($this->uploadDir, $filename);
            $client->setProfilePicturePath('/uploads/clients/' . $filename);
        }

        // Upload document d'identité
        if ($identityFile) {
            $allowedMimeTypes = [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            if (!in_array($identityFile->getMimeType(), $allowedMimeTypes, true)) {
                throw new \InvalidArgumentException('Seuls les fichiers PDF ou images (jpg, png, gif, webp, avif) sont acceptés pour la pièce d\'identité.');
            }
            $filename = uniqid('identity_') . '.' . $identityFile->guessExtension();
            $identityFile->move($this->uploadDir, $filename);

            // Création de la preuve d'identité
            $identityProof = new IdentityProof();
            $identityProof->setType($data['identityType'] ?? 'unknown');
            $identityProof->setFilePath('/uploads/clients/' . $filename);
            $identityProof->setMimeType($identityFile->getMimeType());
            $identityProof->setFileSize($identityFile->getSize());
            $identityProof->setStatus('pending');
            $identityProof->setClient($client);
            $client->addIdentityProof($identityProof);
            $this->em->persist($identityProof);
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
