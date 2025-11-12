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

    #[ORM\ManyToOne(targetEntity: PlayerClass::class, inversedBy: 'characters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PlayerClass $playerClass = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $level = 1;

    #[ORM\Column(type: 'integer')]
    private int $experience = 0;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attributes = null;

    /** @var Collection<int, Quest> */
    #[ORM\ManyToMany(targetEntity: Quest::class)]
    #[ORM\JoinTable(name: 'character_completed_quests')]
    private Collection $completedQuests;

    /** @var Collection<int, CharacterSkill> */
    #[ORM\OneToMany(targetEntity: CharacterSkill::class, mappedBy: 'character', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $skills;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $name, PlayerClass $playerClass)
    {
        $this->name = $name;
        $this->playerClass = $playerClass;
        $this->completedQuests = new ArrayCollection();
        $this->skills = new ArrayCollection();
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
        $this->player = $player;
        $this->touch();
        return $this;
    }

    public function getPlayerClass(): ?PlayerClass
    {
        return $this->playerClass;
    }

    public function setPlayerClass(PlayerClass $playerClass): self
    {
        $this->playerClass = $playerClass;
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
     * @return array<string, mixed>|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, mixed>|null $attributes
     */
    public function setAttributes(?array $attributes): self
    {
        $this->attributes = $attributes;
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
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(CharacterSkill $characterSkill): self
    {
        if (!$this->skills->contains($characterSkill)) {
            $this->skills->add($characterSkill);
            $characterSkill->setCharacter($this);
            $this->touch();
        }

        return $this;
    }

    public function removeSkill(CharacterSkill $characterSkill): self
    {
        if ($this->skills->removeElement($characterSkill)) {
            if ($characterSkill->getCharacter() === $this) {
                $characterSkill->setCharacter(null);
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

