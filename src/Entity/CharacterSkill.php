<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'character_skills')]
#[ORM\UniqueConstraint(name: 'uniq_character_skill', columns: ['character_id', 'skill_id'])]
class CharacterSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlayerCharacter::class, inversedBy: 'characterSkills')]
    #[ORM\JoinColumn(name: 'character_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?PlayerCharacter $character = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'characterSkills')]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Skill $skill = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedAt;

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
        if ($this->character === $character) {
            return $this;
        }

        if ($this->character !== null) {
            $this->character->unregisterCharacterSkill($this);
        }

        $this->character = $character;

        if ($character !== null) {
            $character->registerCharacterSkill($this);
        }

        return $this;
    }

    public function getSkill(): ?Skill
    {
        return $this->skill;
    }

    public function setSkill(?Skill $skill): self
    {
        if ($this->skill === $skill) {
            return $this;
        }

        if ($this->skill !== null) {
            $this->skill->unregisterCharacterSkill($this);
        }

        $this->skill = $skill;

        if ($skill !== null) {
            $skill->registerCharacterSkill($this);
        }

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
}

