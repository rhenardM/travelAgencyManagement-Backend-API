<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["client"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client"])]
    private ?string $Name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client"])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client"])]
    private ?string $LastName = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(["client"])]
    private ?string $number = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client"])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client"])]
    private ?string $Adresse = null;

    #[ORM\Column]
    #[Groups(["client"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client"])]
    private ?string $identityDocumentPath = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Groups(["client"])]
    private ?string $profilePicture = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): static
    {
        $this->Name = $Name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->LastName;
    }

    public function setLastName(string $LastName): static
    {
        $this->LastName = $LastName;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->Adresse;
    }

    public function setAdresse(string $Adresse): static
    {
        $this->Adresse = $Adresse;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIdentityDocumentPath(): ?string
    {
        return $this->identityDocumentPath;
    }

    public function setIdentityDocumentPath(string $identityDocumentPath): static
    {
        $this->identityDocumentPath = $identityDocumentPath;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }
}
