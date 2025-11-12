<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'skill_links')]
#[ORM\UniqueConstraint(name: 'uniq_skill_link_parent_child', columns: ['parent_skill_id', 'child_skill_id'])]
class SkillLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'outgoingLinks')]
    #[ORM\JoinColumn(name: 'parent_skill_id', nullable: false, onDelete: 'CASCADE')]
    private ?Skill $parentSkill = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'incomingLinks')]
    #[ORM\JoinColumn(name: 'child_skill_id', nullable: false, onDelete: 'CASCADE')]
    private ?Skill $childSkill = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $requiresAllParents = true;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentSkill(): ?Skill
    {
        return $this->parentSkill;
    }

    public function setParentSkill(?Skill $parentSkill): self
    {
        $this->parentSkill = $parentSkill;
        return $this;
    }

    public function getChildSkill(): ?Skill
    {
        return $this->childSkill;
    }

    public function setChildSkill(?Skill $childSkill): self
    {
        $this->childSkill = $childSkill;
        return $this;
    }

    public function requiresAllParents(): bool
    {
        return $this->requiresAllParents;
    }

    public function setRequiresAllParents(bool $requiresAllParents): self
    {
        $this->requiresAllParents = $requiresAllParents;
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
        return $this;
    }
}

