<?php

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['chatMsg:read']],
    denormalizationContext: ['groups' => ['chatMsg:write']]
)]

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['chatMsg:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['chatMsg:read'])]
    private ?Md $chat = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['chatMsg:read'])]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['chatMsg:read', 'chatMsg:write'])]
    private ?string $text = null;

    #[ORM\Column]
    #[Groups(['chatMsg:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['chatMsg:read', 'chatMsg:write'])]
    private bool $isRead = false; // <-- nuevo campo

    // Constructor añadido
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): ?Md
    {
        return $this->chat;
    }

    public function setChat(?Md $chat): static
    {
        $this->chat = $chat;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

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
    
    public function getIsRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }
}


