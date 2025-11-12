<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'skills')]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $tier = 'skill';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extraRequirements = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $requiredLevel = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionY = null;

    /** @var Collection<int, PlayerClass> */
    #[ORM\ManyToMany(targetEntity: PlayerClass::class, inversedBy: 'skills')]
    #[ORM\JoinTable(name: 'skill_required_classes')]
    private Collection $requiredClasses;

    /** @var Collection<int, Quest> */
    #[ORM\ManyToMany(targetEntity: Quest::class)]
    #[ORM\JoinTable(name: 'skill_required_quests')]
    private Collection $requiredQuests;

    /** @var Collection<int, Skill> */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'dependentSkills')]
    #[ORM\JoinTable(name: 'skill_prerequisites')]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'prerequisite_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $prerequisiteSkills;

    /** @var Collection<int, Skill> */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'prerequisiteSkills')]
    private Collection $dependentSkills;

    /** @var Collection<int, CharacterSkill> */
    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: CharacterSkill::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $characterSkills;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->requiredClasses = new ArrayCollection();
        $this->requiredQuests = new ArrayCollection();
        $this->prerequisiteSkills = new ArrayCollection();
        $this->dependentSkills = new ArrayCollection();
        $this->characterSkills = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTier(): string
    {
        return $this->tier;
    }

    public function setTier(string $tier): self
    {
        $this->tier = $tier;
        $this->touch();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        $this->touch();
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExtraRequirements(): ?array
    {
        return $this->extraRequirements;
    }

    /**
     * @param array<string, mixed>|null $extraRequirements
     */
    public function setExtraRequirements(?array $extraRequirements): self
    {
        $this->extraRequirements = $extraRequirements;
        $this->touch();
        return $this;
    }

    public function getRequiredLevel(): ?int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(?int $requiredLevel): self
    {
        $this->requiredLevel = $requiredLevel;
        $this->touch();
        return $this;
    }

    public function getPositionX(): ?float
    {
        return $this->positionX;
    }

    public function setPositionX(?float $positionX): self
    {
        $this->positionX = $positionX;
        $this->touch();
        return $this;
    }

    public function getPositionY(): ?float
    {
        return $this->positionY;
    }

    public function setPositionY(?float $positionY): self
    {
        $this->positionY = $positionY;
        $this->touch();
        return $this;
    }

    /**
     * @return Collection<int, PlayerClass>
     */
    public function getRequiredClasses(): Collection
    {
        return $this->requiredClasses;
    }

    public function addRequiredClass(PlayerClass $class): self
    {
        if (!$this->requiredClasses->contains($class)) {
            $this->requiredClasses->add($class);
            $class->registerSkill($this);
            $this->touch();
        }
        return $this;
    }

    public function removeRequiredClass(PlayerClass $class): self
    {
        if ($this->requiredClasses->removeElement($class)) {
            $class->unregisterSkill($this);
            $this->touch();
        }
        return $this;
    }

    /**
     * @return Collection<int, Quest>
     */
    public function getRequiredQuests(): Collection
    {
        return $this->requiredQuests;
    }

    public function addRequiredQuest(Quest $quest): self
    {
        if (!$this->requiredQuests->contains($quest)) {
            $this->requiredQuests->add($quest);
            $this->touch();
        }
        return $this;
    }

    public function removeRequiredQuest(Quest $quest): self
    {
        if ($this->requiredQuests->removeElement($quest)) {
            $this->touch();
        }
        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getPrerequisiteSkills(): Collection
    {
        return $this->prerequisiteSkills;
    }

    public function addPrerequisiteSkill(Skill $skill): self
    {
        if ($skill === $this) {
            return $this;
        }

        if (!$this->prerequisiteSkills->contains($skill)) {
            $this->prerequisiteSkills->add($skill);
            $skill->dependentSkills->add($this);
            $this->touch();
        }
        return $this;
    }

    public function removePrerequisiteSkill(Skill $skill): self
    {
        if ($this->prerequisiteSkills->removeElement($skill)) {
            $skill->dependentSkills->removeElement($this);
            $this->touch();
        }
        return $this;
    }

    public function clearPrerequisiteSkills(): self
    {
        foreach ($this->prerequisiteSkills as $skill) {
            $skill->dependentSkills->removeElement($this);
        }
        $this->prerequisiteSkills->clear();
        $this->touch();
        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getDependentSkills(): Collection
    {
        return $this->dependentSkills;
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
            $characterSkill->setSkill($this);
        }
        return $this;
    }

    public function removeCharacterSkill(CharacterSkill $characterSkill): self
    {
        if ($this->characterSkills->contains($characterSkill)) {
            if ($characterSkill->getSkill() === $this) {
                $characterSkill->setSkill(null);
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

