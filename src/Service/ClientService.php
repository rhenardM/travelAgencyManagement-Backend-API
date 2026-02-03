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
        $client->setCreatedAt(new \DateTimeImmutable());

        // Upload photo de profil
        if ($profilePicture && $profilePicture->getSize() > 0) {
            $allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            $mimeType = $profilePicture->getMimeType();
            if ($mimeType && !in_array($mimeType, $allowedMimeTypes, true)) {
                throw new \InvalidArgumentException('Seules les images (jpg, png, gif, webp, avif) sont acceptées pour la photo de profil.');
            }
            $filename = uniqid('profile_') . '.' . $profilePicture->guessExtension();
            $profilePicture->move($this->uploadDir, $filename);
            $client->setProfilePicturePath('/uploads/' . $filename);
        }

        // Upload document d'identité
        if ($identityFile && $identityFile->getSize() > 0) {
            $allowedMimeTypes = [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            
            // Récupération des informations AVANT le déplacement du fichier
            $mimeType = $identityFile->getMimeType();
            $fileSize = $identityFile->getSize();
            
            if ($mimeType && !in_array($mimeType, $allowedMimeTypes, true)) {
                throw new \InvalidArgumentException('Seuls les fichiers PDF ou images (jpg, png, gif, webp, avif) sont acceptés pour la pièce d\'identité.');
            }
            
            $filename = uniqid('identity_') . '.' . $identityFile->guessExtension();
            $identityFile->move($this->uploadDir, $filename);

            // Déterminer le type de document
            $type = $data['identityType'] ?? null;
            if (!$type || $type === 'unknown') {
                // Détection automatique selon le mime type
                $type = match($mimeType) {
                    'application/pdf' => 'document',
                    'image/jpeg', 'image/jpg', 'image/png' => 'photo_id',
                    default => 'other'
                };
            }

            // Création de la preuve d'identité
            $identityProof = new IdentityProof();
            $identityProof->setType($type);
            $identityProof->setFilePath('/uploads/' . $filename);
            $identityProof->setMimeType($mimeType);
            $identityProof->setFileSize($fileSize);
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
     * Récupère tous les clients avec pagination
     */
    public function getAllClients(int $page = 1, int $limit = 10): array
    {
        $repository = $this->em->getRepository(Client::class);
        
        $offset = ($page - 1) * $limit;
        $clients = $repository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $total = $repository->count([]);
        
        return [
            'data' => $clients,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit)
            ]
        ];
    }

    /**
     * Récupère un client par ID
     */
    public function getClientById(int $id): ?Client
    {
        return $this->em->getRepository(Client::class)->find($id);
    }

    /**
     * Met à jour un client
     */
    public function updateClient(Client $client, array $data, ?UploadedFile $profilePicture = null, ?UploadedFile $identityFile = null): Client
    {
        // Mise à jour des informations du client
        if (isset($data['name'])) {
            $client->setName($data['name']);
        }
        if (isset($data['firstName'])) {
            $client->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $client->setLastName($data['lastName']);
        }
        if (isset($data['phone'])) {
            $client->setPhone($data['phone']);
        }
        if (isset($data['email'])) {
            $client->setEmail($data['email']);
        }
        if (isset($data['adresse'])) {
            $client->setAdresse($data['adresse']);
        }

        // Upload nouvelle photo de profil si fournie
        if ($profilePicture && $profilePicture->getSize() > 0) {
            $allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            $mimeType = $profilePicture->getMimeType();
            if ($mimeType && !in_array($mimeType, $allowedMimeTypes, true)) {
                throw new \InvalidArgumentException('Seules les images (jpg, png, gif, webp, avif) sont acceptées pour la photo de profil.');
            }
            
            // Supprimer l'ancienne photo si elle existe
            if ($client->getProfilePicturePath()) {
                $oldFile = $this->uploadDir . '/' . basename($client->getProfilePicturePath());
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            $filename = uniqid('profile_') . '.' . $profilePicture->guessExtension();
            $profilePicture->move($this->uploadDir, $filename);
            $client->setProfilePicturePath('/uploads/' . $filename);
        }

        // Upload nouveau document d'identité si fourni
        if ($identityFile && $identityFile->getSize() > 0) {
            $allowedMimeTypes = [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/jpg',
            ];
            
            $mimeType = $identityFile->getMimeType();
            $fileSize = $identityFile->getSize();
            
            if ($mimeType && !in_array($mimeType, $allowedMimeTypes, true)) {
                throw new \InvalidArgumentException('Seuls les fichiers PDF ou images (jpg, png, gif, webp, avif) sont acceptés pour la pièce d\'identité.');
            }
            
            $filename = uniqid('identity_') . '.' . $identityFile->guessExtension();
            $identityFile->move($this->uploadDir, $filename);

            // Déterminer le type de document
            $type = $data['identityType'] ?? null;
            if (!$type || $type === 'unknown') {
                $type = match($mimeType) {
                    'application/pdf' => 'document',
                    'image/jpeg', 'image/jpg', 'image/png' => 'photo_id',
                    default => 'other'
                };
            }

            // Création d'une nouvelle preuve d'identité
            $identityProof = new IdentityProof();
            $identityProof->setType($type);
            $identityProof->setFilePath('/uploads/' . $filename);
            $identityProof->setMimeType($mimeType);
            $identityProof->setFileSize($fileSize);
            $identityProof->setStatus('pending');
            $identityProof->setClient($client);
            $client->addIdentityProof($identityProof);
            $this->em->persist($identityProof);
        }

        $this->em->flush();

        return $client;
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
