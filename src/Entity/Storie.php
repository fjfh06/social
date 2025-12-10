<?php

namespace App\Entity;

use App\Repository\StorieRepository;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\User;

#[ApiResource(
    normalizationContext: ['groups' => ['storie:read']],
    denormalizationContext: ['groups' => ['storie:write']]
)]

#[ORM\Entity(repositoryClass: StorieRepository::class)]
class Storie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['storie:read'])]
    private ?int $id = null;

    #[Groups(['storie:read', 'storie:write'])]
    #[ORM\Column(length: 255)]
    private ?string $img = null;

    #[ORM\Column]
    #[Groups(['storie:read', 'storie:write'])]
    private ?\DateTime $datetime = null;

    #[ORM\Column]
    #[Groups(['storie:read', 'storie:write'])]
    private ?bool $visible = null;

    #[ORM\ManyToOne(inversedBy: 'stories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['storie:read'])]
    private ?User $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(string $img): static
    {
        $this->img = $img;

        return $this;
    }

    public function getDatetime(): ?\DateTime
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTime $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

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
}
