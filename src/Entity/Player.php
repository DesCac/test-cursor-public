<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'players')]
#[ORM\UniqueConstraint(name: 'uniq_players_tg_user_id', columns: ['tg_user_id'])]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $tgUserId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $displayName = null;

    /** @var Collection<int, PlayerCharacter> */
    #[ORM\OneToMany(mappedBy: 'player', targetEntity: PlayerCharacter::class, cascade: ['persist'], orphanRemoval: true)]
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
        $this->touch();
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
        $this->touch();
        return $this;
    }

    /**
     * @return Collection<int, PlayerCharacter>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function registerCharacter(PlayerCharacter $character): void
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            $this->touch();
        }
    }

    public function unregisterCharacter(PlayerCharacter $character): void
    {
        if ($this->characters->removeElement($character)) {
            $this->touch();
        }
    }

    public function addCharacter(PlayerCharacter $character): self
    {
        if (!$this->characters->contains($character)) {
            $this->registerCharacter($character);
            $character->setPlayer($this);
        }
        return $this;
    }

    public function removeCharacter(PlayerCharacter $character): self
    {
        if ($this->characters->contains($character)) {
            if ($character->getPlayer() === $this) {
                $character->setPlayer(null);
            }
        }
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

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

