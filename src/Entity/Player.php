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

    #[ORM\Column(type: 'string', length: 255)]
    private string $displayName;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, PlayerCharacter> */
    #[ORM\OneToMany(
        targetEntity: PlayerCharacter::class,
        mappedBy: 'player',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $characters;

    public function __construct(string $tgUserId, string $displayName)
    {
        $this->tgUserId = $tgUserId;
        $this->displayName = $displayName;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->characters = new ArrayCollection();
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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
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

    public function addCharacter(PlayerCharacter $character): self
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            $character->setPlayer($this);
            $this->touch();
        }

        return $this;
    }

    public function removeCharacter(PlayerCharacter $character): self
    {
        if ($this->characters->removeElement($character)) {
            if ($character->getPlayer() === $this) {
                $character->setPlayer(null);
            }
            $this->touch();
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

