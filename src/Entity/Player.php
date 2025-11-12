<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'players')]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $tgUserId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $username = null;

    /** @var Collection<int, PlayerCharacter> */
    #[ORM\OneToMany(targetEntity: PlayerCharacter::class, mappedBy: 'player', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $characters;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->characters = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTgUserId(): string
    {
        return $this->tgUserId;
    }

    public function setTgUserId(string $tgUserId): self
    {
        $this->tgUserId = $tgUserId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, PlayerCharacter>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(PlayerCharacter $character): self
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            $character->setPlayer($this);
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function removeCharacter(PlayerCharacter $character): self
    {
        if ($this->characters->removeElement($character)) {
            if ($character->getPlayer() === $this) {
                $character->setPlayer(null);
            }
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
