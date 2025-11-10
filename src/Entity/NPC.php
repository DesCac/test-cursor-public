<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'npcs')]
class NPC
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, DialogNode> */
    #[ORM\OneToMany(targetEntity: DialogNode::class, mappedBy: 'npc', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dialogNodes;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->dialogNodes = new ArrayCollection();
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
     * @return Collection<int, DialogNode>
     */
    public function getDialogNodes(): Collection
    {
        return $this->dialogNodes;
    }

    public function addDialogNode(DialogNode $dialogNode): self
    {
        if (!$this->dialogNodes->contains($dialogNode)) {
            $this->dialogNodes->add($dialogNode);
            $dialogNode->setNpc($this);
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function removeDialogNode(DialogNode $dialogNode): self
    {
        if ($this->dialogNodes->removeElement($dialogNode)) {
            if ($dialogNode->getNpc() === $this) {
                $dialogNode->setNpc(null);
            }
        }
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
