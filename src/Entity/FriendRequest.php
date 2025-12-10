<?php

namespace App\Entity;

use App\Repository\FriendRequestRepository;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['friendRequest:read']],
    denormalizationContext: ['groups' => ['friendRequest:write']]
)]
#[ORM\Entity(repositoryClass: FriendRequestRepository::class)]
class FriendRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['friendRequest:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['friendRequest:read', 'friendRequest:write'])]
    private ?string $status = 'PENDING';

    #[ORM\Column]
    #[Groups(['friendRequest:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'sentFriendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receivedFriendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'PENDING';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;
        return $this;
    }
}
