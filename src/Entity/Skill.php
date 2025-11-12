<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'skills')]
#[ORM\UniqueConstraint(name: 'uniq_skills_slug', columns: ['slug'])]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $requiredLevel = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $availabilityRules = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionY = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, PlayerClass> */
    #[ORM\ManyToMany(targetEntity: PlayerClass::class, inversedBy: 'skills')]
    #[ORM\JoinTable(name: 'skill_required_classes')]
    private Collection $requiredClasses;

    /** @var Collection<int, Quest> */
    #[ORM\ManyToMany(targetEntity: Quest::class)]
    #[ORM\JoinTable(name: 'skill_required_quests')]
    private Collection $requiredQuests;

    /** @var Collection<int, SkillLink> */
    #[ORM\OneToMany(targetEntity: SkillLink::class, mappedBy: 'childSkill', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $incomingLinks;

    /** @var Collection<int, SkillLink> */
    #[ORM\OneToMany(targetEntity: SkillLink::class, mappedBy: 'parentSkill', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $outgoingLinks;

    /** @var Collection<int, CharacterSkill> */
    #[ORM\OneToMany(targetEntity: CharacterSkill::class, mappedBy: 'skill', cascade: ['remove'])]
    private Collection $characterSkills;

    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->requiredClasses = new ArrayCollection();
        $this->requiredQuests = new ArrayCollection();
        $this->incomingLinks = new ArrayCollection();
        $this->outgoingLinks = new ArrayCollection();
        $this->characterSkills = new ArrayCollection();
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
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

    /**
     * @return array<string, mixed>|null
     */
    public function getAvailabilityRules(): ?array
    {
        return $this->availabilityRules;
    }

    /**
     * @param array<string, mixed>|null $availabilityRules
     */
    public function setAvailabilityRules(?array $availabilityRules): self
    {
        $this->availabilityRules = $availabilityRules;
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
            if (!$class->getSkills()->contains($this)) {
                $class->getSkills()->add($this);
            }
            $this->touch();
        }

        return $this;
    }

    public function removeRequiredClass(PlayerClass $class): self
    {
        if ($this->requiredClasses->removeElement($class)) {
            if ($class->getSkills()->contains($this)) {
                $class->getSkills()->removeElement($this);
            }
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
     * @return Collection<int, SkillLink>
     */
    public function getIncomingLinks(): Collection
    {
        return $this->incomingLinks;
    }

    public function addIncomingLink(SkillLink $link): self
    {
        if (!$this->incomingLinks->contains($link)) {
            $this->incomingLinks->add($link);
            $link->setChildSkill($this);
            $this->touch();
        }

        return $this;
    }

    public function removeIncomingLink(SkillLink $link): self
    {
        if ($this->incomingLinks->removeElement($link)) {
            if ($link->getChildSkill() === $this) {
                $link->setChildSkill(null);
            }
            $this->touch();
        }

        return $this;
    }

    /**
     * @return Collection<int, SkillLink>
     */
    public function getOutgoingLinks(): Collection
    {
        return $this->outgoingLinks;
    }

    public function addOutgoingLink(SkillLink $link): self
    {
        if (!$this->outgoingLinks->contains($link)) {
            $this->outgoingLinks->add($link);
            $link->setParentSkill($this);
            $this->touch();
        }

        return $this;
    }

    public function removeOutgoingLink(SkillLink $link): self
    {
        if ($this->outgoingLinks->removeElement($link)) {
            if ($link->getParentSkill() === $this) {
                $link->setParentSkill(null);
            }
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

    public function addCharacterSkill(CharacterSkill $characterSkill): self
    {
        if (!$this->characterSkills->contains($characterSkill)) {
            $this->characterSkills->add($characterSkill);
            $characterSkill->setSkill($this);
            $this->touch();
        }

        return $this;
    }

    public function removeCharacterSkill(CharacterSkill $characterSkill): self
    {
        if ($this->characterSkills->removeElement($characterSkill)) {
            if ($characterSkill->getSkill() === $this) {
                $characterSkill->setSkill(null);
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

