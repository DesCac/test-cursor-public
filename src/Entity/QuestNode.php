<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quest_nodes')]
class QuestNode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quest::class, inversedBy: 'logicNodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Quest $quest = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $nodeType; // 'start', 'objective', 'condition', 'reward', 'end'

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $conditions = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $positionY = null;

    /** @var Collection<int, QuestConnection> */
    #[ORM\OneToMany(targetEntity: QuestConnection::class, mappedBy: 'sourceNode', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $outgoingConnections;

    /** @var Collection<int, QuestConnection> */
    #[ORM\OneToMany(targetEntity: QuestConnection::class, mappedBy: 'targetNode', cascade: ['persist', 'remove'], orphanRemoval: true)]
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

    public function getQuest(): ?Quest
    {
        return $this->quest;
    }

    public function setQuest(?Quest $quest): self
    {
        $this->quest = $quest;
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

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getConditions(): ?array
    {
        return $this->conditions;
    }

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
     * @return Collection<int, QuestConnection>
     */
    public function getOutgoingConnections(): Collection
    {
        return $this->outgoingConnections;
    }

    /**
     * @return Collection<int, QuestConnection>
     */
    public function getIncomingConnections(): Collection
    {
        return $this->incomingConnections;
    }
}
