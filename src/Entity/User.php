<?php


namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Post;
use App\Entity\FriendRequest;
use App\Entity\Storie;
use App\Entity\Md;

#[ApiResource(
    // Normalization context utiliza el grupo principal 'user:read'
    normalizationContext: ['groups' => ['user:read']], 
    denormalizationContext: ['groups' => ['user:write']],
    forceEager: false 
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // Se añade 'user:read_nested' para cuando este usuario aparece dentro de otro.
    #[Groups(['user:read', 'user:read_nested', 'post:read', 'friend_request:read', 'md:read'])] 
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write', 'user:read_nested', 'post:read', 'friend_request:read', 'md:read'])]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read', 'user:read_nested'])] 
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    // ===================== Relaciones EXPANDIDAS (Añadiendo grupos :read_nested) =====================

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author')]
    // Agregamos 'post:read_nested' para expandir los objetos Post
    #[Groups(['user:read', 'post:read_nested'])] 
    private Collection $posts;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'likes')]
    // Agregamos 'post:read_nested'
    #[Groups(['user:read', 'post:read_nested'])] 
    private Collection $likes;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'followers')]
    // Se utiliza 'user:read_nested' para expandir el objeto User, pero DEBEMOS QUITAR 'user:read' de aquí
    // para evitar que un usuario anidado se intente serializar con su grupo principal, deteniendo la recursión.
    // **IMPORTANTE: En la capa raíz se usa 'user:read', en la capa anidada se usará 'user:read_nested'.**
    #[Groups(['user:read', 'user:read_nested'])] 
    private Collection $following;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'following')]
    // Se utiliza 'user:read_nested' para expandir el objeto User anidado.
    #[Groups(['user:read', 'user:read_nested'])] 
    private Collection $followers;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write', 'user:read_nested'])]
    private ?bool $isPrivate = null;

    /**
     * @var Collection<int, FriendRequest>
     */
    #[ORM\OneToMany(targetEntity: FriendRequest::class, mappedBy: 'sender')]
    // Agregamos 'friend_request:read_nested'
    #[Groups(['user:read', 'friend_request:read_nested'])] 
    private Collection $sentFriendRequests;

    /**
     * @var Collection<int, FriendRequest>
     */
    #[ORM\OneToMany(targetEntity: FriendRequest::class, mappedBy: 'receiver')]
    // Agregamos 'friend_request:read_nested'
    #[Groups(['user:read', 'friend_request:read_nested'])] 
    private Collection $receivedFriendRequests;

    /**
     * @var Collection<int, Storie>
     */
    #[ORM\OneToMany(targetEntity: Storie::class, mappedBy: 'author')]
    // Agregamos 'storie:read_nested' para expandir los objetos Storie
    #[Groups(['user:read', 'storie:read_nested'])] 
    private Collection $stories;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class)]
    // Agregamos 'post:read_nested'
    #[Groups(['user:read', 'post:read_nested'])] 
    private Collection $reposts;

    /**
     * @var Collection<int, Md>
     */
    #[ORM\ManyToMany(targetEntity: Md::class, mappedBy: 'users')]
    // Agregamos 'md:read_nested'
    #[Groups(['user:read', 'md:read_nested'])] 
    private Collection $mds;

    // ===================== Constructor y métodos (sin cambios funcionales) =====================

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->mds = new ArrayCollection();
        $this->sentFriendRequests = new ArrayCollection();
        $this->receivedFriendRequests = new ArrayCollection();
        $this->stories = new ArrayCollection();
        $this->reposts = new ArrayCollection();
        $this->roles = [];
        $this->isPrivate = false;
    }

    // ... (Resto de Getters y Setters)
    // El resto del código de la entidad (getters, setters, métodos de colección) se mantiene como lo proporcionaste.
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password ?? ''); 

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void {}
    
    // ... (Resto de métodos de colección sin cambios)
    
    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Post $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            // $like->addLike($this); 
        }

        return $this;
    }

    public function removeLike(Post $like): static
    {
        if ($this->likes->removeElement($like)) {
            // $like->removeLike($this); 
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(self $following): static
    {
        if (!$this->following->contains($following)) {
            $this->following->add($following);
        }

        return $this;
    }

    public function removeFollowing(self $following): static
    {
        $this->following->removeElement($following);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(self $follower): static
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
            // $follower->addFollowing($this); 
        }

        return $this;
    }

    public function removeFollower(self $follower): static
    {
        if ($this->followers->removeElement($follower)) {
            // $follower->removeFollowing($this);
        }

        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getSentFriendRequests(): Collection
    {
        return $this->sentFriendRequests;
    }

    public function addSentFriendRequest(FriendRequest $sentFriendRequest): static
    {
        if (!$this->sentFriendRequests->contains($sentFriendRequest)) {
            $this->sentFriendRequests->add($sentFriendRequest);
            $sentFriendRequest->setSender($this);
        }

        return $this;
    }

    public function removeSentFriendRequest(FriendRequest $sentFriendRequest): static
    {
        if ($this->sentFriendRequests->removeElement($sentFriendRequest)) {
            if ($sentFriendRequest->getSender() === $this) {
                $sentFriendRequest->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getReceivedFriendRequests(): Collection
    {
        return $this->receivedFriendRequests;
    }

    public function addReceivedFriendRequest(FriendRequest $receivedFriendRequest): static
    {
        if (!$this->receivedFriendRequests->contains($receivedFriendRequest)) {
            $this->receivedFriendRequests->add($receivedFriendRequest);
            $receivedFriendRequest->setReceiver($this);
        }

        return $this;
    }

    public function removeReceivedFriendRequest(FriendRequest $receivedFriendRequest): static
    {
        if ($this->receivedFriendRequests->removeElement($receivedFriendRequest)) {
            if ($receivedFriendRequest->getReceiver() === $this) {
                $receivedFriendRequest->setReceiver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Storie>
     */
    public function getStories(): Collection
    {
        return $this->stories;
    }

    public function addStory(Storie $story): static
    {
        if (!$this->stories->contains($story)) {
            $this->stories->add($story);
            $story->setAuthor($this);
        }

        return $this;
    }

    public function removeStory(Storie $story): static
    {
        if ($this->stories->removeElement($story)) {
            if ($story->getAuthor() === $this) {
                $story->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getReposts(): Collection
    {
        return $this->reposts;
    }

    public function addRepost(Post $repost): static
    {
        if (!$this->reposts->contains($repost)) {
            $this->reposts->add($repost);
        }

        return $this;
    }

    public function removeRepost(Post $repost): static
    {
        $this->reposts->removeElement($repost);

        return $this;
    }

    /**
     * @return Collection<int, Md>
     */
    public function getMds(): Collection
    {
        return $this->mds;
    }

    public function addMd(Md $md): static
    {
        if (!$this->mds->contains($md)) {
            $this->mds->add($md);
            $md->addUser($this); 
        }

        return $this;
    }

    public function removeMd(Md $md): static
    {
        if ($this->mds->removeElement($md)) {
            $md->removeUser($this); 
        }

        return $this;
    }
}