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

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, Skill> */
    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'children')]
    #[ORM\JoinTable(
        name: 'skill_dependencies',
        joinColumns: [new ORM\JoinColumn(name: 'child_skill_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'parent_skill_id', referencedColumnName: 'id')]
    )]
    private Collection $parents;

    /** @var Collection<int, Skill> */
    #[ORM\ManyToMany(targetEntity: Skill::class, mappedBy: 'parents')]
    private Collection $children;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $unlockConditions = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $effects = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionY = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->parents = new ArrayCollection();
        $this->children = new ArrayCollection();
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
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function addParent(Skill $parent): self
    {
        if (!$this->parents->contains($parent)) {
            $this->parents->add($parent);
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function removeParent(Skill $parent): self
    {
        $this->parents->removeElement($parent);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUnlockConditions(): ?array
    {
        return $this->unlockConditions;
    }

    /**
     * @param array<string, mixed>|null $unlockConditions
     */
    public function setUnlockConditions(?array $unlockConditions): self
    {
        $this->unlockConditions = $unlockConditions;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEffects(): ?array
    {
        return $this->effects;
    }

    /**
     * @param array<string, mixed>|null $effects
     */
    public function setEffects(?array $effects): self
    {
        $this->effects = $effects;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPositionX(): ?float
    {
        return $this->positionX;
    }

    public function setPositionX(?float $positionX): self
    {
        $this->positionX = $positionX;
        return $this;
    }

    public function getPositionY(): ?float
    {
        return $this->positionY;
    }

    public function setPositionY(?float $positionY): self
    {
        $this->positionY = $positionY;
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
