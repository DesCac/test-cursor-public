<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'player_characters')]
class PlayerCharacter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'characters')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Player $player = null;

    #[ORM\ManyToOne(targetEntity: PlayerClass::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?PlayerClass $class = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $level = 1;

    #[ORM\Column(type: 'integer')]
    private int $experience = 0;

    /** @var Collection<int, Quest> */
    #[ORM\ManyToMany(targetEntity: Quest::class)]
    #[ORM\JoinTable(name: 'character_completed_quests')]
    private Collection $completedQuests;

    /** @var Collection<int, CharacterSkill> */
    #[ORM\OneToMany(mappedBy: 'character', targetEntity: CharacterSkill::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $characterSkills;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->completedQuests = new ArrayCollection();
        $this->characterSkills = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): self
    {
        if ($this->player === $player) {
            return $this;
        }

        if ($this->player !== null) {
            $this->player->unregisterCharacter($this);
        }

        $this->player = $player;

        if ($player !== null) {
            $player->registerCharacter($this);
        }

        $this->touch();
        return $this;
    }

    public function getClass(): ?PlayerClass
    {
        return $this->class;
    }

    public function setClass(?PlayerClass $class): self
    {
        $this->class = $class;
        $this->touch();
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->touch();
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = max(1, $level);
        $this->touch();
        return $this;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function setExperience(int $experience): self
    {
        $this->experience = max(0, $experience);
        $this->touch();
        return $this;
    }

    /**
     * @return Collection<int, Quest>
     */
    public function getCompletedQuests(): Collection
    {
        return $this->completedQuests;
    }

    public function addCompletedQuest(Quest $quest): self
    {
        if (!$this->completedQuests->contains($quest)) {
            $this->completedQuests->add($quest);
            $this->touch();
        }
        return $this;
    }

    public function removeCompletedQuest(Quest $quest): self
    {
        if ($this->completedQuests->removeElement($quest)) {
            $this->touch();
        }
        return $this;
    }

    /**
     * @return Collection<int, CharacterSkill>
     */
    public function getCharacterSkills(): Collection
    {
        return $this->characterSkills;
    }

    public function registerCharacterSkill(CharacterSkill $characterSkill): void
    {
        if (!$this->characterSkills->contains($characterSkill)) {
            $this->characterSkills->add($characterSkill);
            $this->touch();
        }
    }

    public function unregisterCharacterSkill(CharacterSkill $characterSkill): void
    {
        if ($this->characterSkills->removeElement($characterSkill)) {
            $this->touch();
        }
    }

    public function addCharacterSkill(CharacterSkill $characterSkill): self
    {
        if (!$this->characterSkills->contains($characterSkill)) {
            $this->registerCharacterSkill($characterSkill);
            $characterSkill->setCharacter($this);
        }
        return $this;
    }

    public function removeCharacterSkill(CharacterSkill $characterSkill): self
    {
        if ($this->characterSkills->contains($characterSkill)) {
            if ($characterSkill->getCharacter() === $this) {
                $characterSkill->setCharacter(null);
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

