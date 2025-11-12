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

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $level = 1;

    #[ORM\ManyToOne(targetEntity: CharacterClass::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CharacterClass $characterClass = null;

    /** @var Collection<int, Skill> */
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    #[ORM\JoinTable(name: 'player_character_skills')]
    private Collection $unlockedSkills;

    /** @var array<int>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $completedQuestIds = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $inventory = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->unlockedSkills = new ArrayCollection();
        $this->completedQuestIds = [];
        $this->inventory = [];
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
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCharacterClass(): ?CharacterClass
    {
        return $this->characterClass;
    }

    public function setCharacterClass(?CharacterClass $characterClass): self
    {
        $this->characterClass = $characterClass;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getUnlockedSkills(): Collection
    {
        return $this->unlockedSkills;
    }

    public function addUnlockedSkill(Skill $skill): self
    {
        if (!$this->unlockedSkills->contains($skill)) {
            $this->unlockedSkills->add($skill);
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function removeUnlockedSkill(Skill $skill): self
    {
        $this->unlockedSkills->removeElement($skill);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return array<int>|null
     */
    public function getCompletedQuestIds(): ?array
    {
        return $this->completedQuestIds;
    }

    /**
     * @param array<int>|null $completedQuestIds
     */
    public function setCompletedQuestIds(?array $completedQuestIds): self
    {
        $this->completedQuestIds = $completedQuestIds;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function addCompletedQuest(int $questId): self
    {
        if ($this->completedQuestIds === null) {
            $this->completedQuestIds = [];
        }
        if (!in_array($questId, $this->completedQuestIds, true)) {
            $this->completedQuestIds[] = $questId;
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getInventory(): ?array
    {
        return $this->inventory;
    }

    /**
     * @param array<string, mixed>|null $inventory
     */
    public function setInventory(?array $inventory): self
    {
        $this->inventory = $inventory;
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
