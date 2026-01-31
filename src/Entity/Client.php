<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Entity\IdentityProof;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['client'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['client'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['client'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client'])]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['client'])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Photo de profil du client
     * Ex: /uploads/clients/profile/client_123.jpg
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['client'])]
    private ?string $profilePicturePath = null;

    /**
     * Preuves d'identité (image ou PDF)
     */
    #[ORM\OneToMany(
        mappedBy: 'client',
        targetEntity: IdentityProof::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups(['client'])]
    private Collection $identityProofs;

    public function __construct()
    {
        $this->identityProofs = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // =======================
    // GETTERS & SETTERS
    // =======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->address;
    }

    public function setAdresse(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    #[Groups(['client'])]
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getProfilePicturePath(): ?string
    {
        return $this->profilePicturePath;
    }

    public function setProfilePicturePath(?string $path): self
    {
        $this->profilePicturePath = $path;
        return $this;
    }

    /**
     * @return Collection<int, IdentityProof>
     */
    public function getIdentityProofs(): Collection
    {
        return $this->identityProofs;
    }

    public function addIdentityProof(IdentityProof $identityProof): self
    {
        if (!$this->identityProofs->contains($identityProof)) {
            $this->identityProofs->add($identityProof);
            $identityProof->setClient($this);
        }

        return $this;
    }

    public function removeIdentityProof(IdentityProof $identityProof): self
    {
        if ($this->identityProofs->removeElement($identityProof)) {
            if ($identityProof->getClient() === $this) {
                $identityProof->setClient(null);
            }
        }

        return $this;
    }
}
