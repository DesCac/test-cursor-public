<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'character_skills')]
#[ORM\UniqueConstraint(name: 'uniq_character_skills_pair', columns: ['character_id', 'skill_id'])]
class CharacterSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlayerCharacter::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'character_id', nullable: false, onDelete: 'CASCADE')]
    private ?PlayerCharacter $character = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'characterSkills')]
    #[ORM\JoinColumn(name: 'skill_id', nullable: false, onDelete: 'CASCADE')]
    private ?Skill $skill = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedAt;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $unlockContext = null;

    public function __construct()
    {
        $this->unlockedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacter(): ?PlayerCharacter
    {
        return $this->character;
    }

    public function setCharacter(?PlayerCharacter $character): self
    {
        $this->character = $character;
        return $this;
    }

    public function getSkill(): ?Skill
    {
        return $this->skill;
    }

    public function setSkill(?Skill $skill): self
    {
        $this->skill = $skill;
        return $this;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function setUnlockedAt(\DateTimeImmutable $unlockedAt): self
    {
        $this->unlockedAt = $unlockedAt;
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUnlockContext(): ?array
    {
        return $this->unlockContext;
    }

    /**
     * @param array<string, mixed>|null $unlockContext
     */
    public function setUnlockContext(?array $unlockContext): self
    {
        $this->unlockContext = $unlockContext;
        return $this;
    }
}

