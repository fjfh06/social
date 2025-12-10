<?php

namespace App\Entity;

use App\Repository\MdRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['md:read']],
    denormalizationContext: ['groups' => ['md:write']]
)]

#[ORM\Entity(repositoryClass: MdRepository::class)]
class Md
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'mds')]
    #[ORM\JoinTable(name: 'md_user')] // <-- Añadido
    #[Groups(['md:read'])]
    private Collection $users;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['md:read','md:write'])]
    private ?string $text = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['md:read','md:write'])]
    private ?string $img = null;

    #[ORM\Column]
    #[Groups(['md:read'])]
    private ?\DateTimeImmutable $daySent = null;

    /**
     * @var Collection<int, ChatMessage>
     */
    #[ORM\OneToMany(mappedBy: 'chat', targetEntity: ChatMessage::class, cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    #[Groups(['md:read'])]
    private Collection $messages;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addMd($this); // <-- Sincroniza el inverse side
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeMd($this); // <-- Sincroniza el inverse side
        }

        return $this;
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

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): static
    {
        $this->img = $img;

        return $this;
    }

    public function getDaySent(): ?\DateTimeImmutable
    {
        return $this->daySent;
    }

    public function setDaySent(\DateTimeImmutable $daySent): static
    {
        $this->daySent = $daySent;

        return $this;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ChatMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setChat($this);
        }

        return $this;
    }

    public function removeMessage(ChatMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getChat() === $this) {
                $message->setChat(null);
            }
        }

        return $this;
    }
}
