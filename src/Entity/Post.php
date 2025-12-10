<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    // Añadimos el grupo post:read_nested para poder usarlo en las relaciones del User
    normalizationContext: ['groups' => ['post:read', 'post:read_nested']], 
    denormalizationContext: ['groups' => ['post:write']]
)]

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['post:read', 'post:read_nested', 'user:read'])] // Añadimos :read_nested y el grupo User
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['post:read', 'post:write', 'post:read_nested'])] // Añadimos :read_nested
    private ?string $text = null;

    #[ORM\Column]
    #[Groups(['post:read', 'post:read_nested'])] // Añadimos :read_nested
    private ?\DateTime $postDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['post:read', 'post:write', 'post:read_nested'])] // Añadimos :read_nested
    private ?string $img = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    // ⚠️ CRÍTICO: La relación 'author' debe usar un grupo que NO expanda completamente al User 
    // cuando el Post está anidado (post:read_nested). Si no lo hacemos, volvemos a User y causamos recursión.
    #[Groups(['post:read', 'post:write', 'post:read_nested'])] 
    private ?User $author = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'likes')]
    // 💡 EXPANDIR LIKES: Usamos 'user:read_nested' para expandir a los usuarios que dieron like.
    // Solo lectura. La acción de dar like se hace vía un endpoint separado.
    #[Groups(['post:read', 'user:read_nested'])] 
    private Collection $likes;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'reposts')]
    // 💡 EXPANDIR REPOSTED BY: Usamos 'user:read_nested' para expandir a los usuarios que hicieron repost.
    #[Groups(['post:read', 'user:read_nested'])] 
    private Collection $repostedBy;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
        $this->repostedBy = new ArrayCollection();
        // Inicializar postDate si es necesario
        // $this->postDate = new \DateTime(); 
    }

    // ===================== Getters y Setters (sin cambios funcionales) =====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getPostDate(): ?\DateTime
    {
        return $this->postDate;
    }

    public function setPostDate(\DateTime $postDate): static
    {
        $this->postDate = $postDate;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): static
    {
        $this->img = $img;

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

    /**
     * @return Collection<int, User>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(User $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
        }

        return $this;
    }

    public function removeLike(User $like): static
    {
        $this->likes->removeElement($like);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getRepostedBy(): Collection
    {
        return $this->repostedBy;
    }

    public function addRepostedBy(User $repostedBy): static
    {
        if (!$this->repostedBy->contains($repostedBy)) {
            $this->repostedBy->add($repostedBy);
            $repostedBy->addRepost($this);
        }

        return $this;
    }

    public function removeRepostedBy(User $repostedBy): static
    {
        if ($this->repostedBy->removeElement($repostedBy)) {
            $repostedBy->removeRepost($this);
        }

        return $this;
    }
}