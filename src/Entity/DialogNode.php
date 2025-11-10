<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'dialog_nodes')]
class DialogNode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NPC::class, inversedBy: 'dialogNodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?NPC $npc = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $nodeType; // 'start', 'dialog', 'choice', 'action', 'end'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $conditions = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionY = null;

    /** @var Collection<int, DialogConnection> */
    #[ORM\OneToMany(targetEntity: DialogConnection::class, mappedBy: 'sourceNode', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $outgoingConnections;

    /** @var Collection<int, DialogConnection> */
    #[ORM\OneToMany(targetEntity: DialogConnection::class, mappedBy: 'targetNode', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $incomingConnections;

    public function __construct()
    {
        $this->outgoingConnections = new ArrayCollection();
        $this->incomingConnections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNpc(): ?NPC
    {
        return $this->npc;
    }

    public function setNpc(?NPC $npc): self
    {
        $this->npc = $npc;
        return $this;
    }

    public function getNodeType(): string
    {
        return $this->nodeType;
    }

    public function setNodeType(string $nodeType): self
    {
        $this->nodeType = $nodeType;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConditions(): ?array
    {
        return $this->conditions;
    }

    /**
     * @param array<string, mixed>|null $conditions
     */
    public function setConditions(?array $conditions): self
    {
        $this->conditions = $conditions;
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

    /**
     * @return Collection<int, DialogConnection>
     */
    public function getOutgoingConnections(): Collection
    {
        return $this->outgoingConnections;
    }

    /**
     * @return Collection<int, DialogConnection>
     */
    public function getIncomingConnections(): Collection
    {
        return $this->incomingConnections;
    }
}
