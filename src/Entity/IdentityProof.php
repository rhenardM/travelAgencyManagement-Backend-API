<?php

namespace App\Entity;

use App\Repository\IdentityProofRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: IdentityProofRepository::class)]
#[ORM\HasLifecycleCallbacks]
class IdentityProof
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['client'])]
    private ?int $id = null;

    /**
     * Type de document
     * Ex: passport, national_id, voter_card
     */
    #[ORM\Column(length: 50)]
    #[Groups(['client'])]
    private ?string $type = null;

    /**
     * Chemin du fichier (image ou PDF)
     * Ex: /uploads/clients/identity/client_12_passport.jpg
     */
    #[ORM\Column(length: 255)]
    #[Groups(['client'])]
    private ?string $filePath = null;

    /**
     * Mime type du fichier
     * image/jpeg, image/png, application/pdf
     */
    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    /**
     * Taille du fichier (en bytes)
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fileSize = null;

    /**
     * Statut de vérification
     * pending | approved | rejected
     */
    #[ORM\Column(length: 20)]
    #[Groups(['client'])]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $uploadedAt = null;

    /**
     * Compteur de téléchargements
     * Indique combien de fois le document a été téléchargé/consulté
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['client'])]
    private int $downloadCount = 0;

    /**
     * Client associé
     */
    #[ORM\ManyToOne(inversedBy: 'identityProofs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    // =======================
    // GETTERS & SETTERS
    // =======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getDownloadCount(): int
    {
        return $this->downloadCount;
    }

    public function incrementDownloadCount(): self
    {
        $this->downloadCount++;
        return $this;
    }
}