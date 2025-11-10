<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quest_connections')]
class QuestConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuestNode::class, inversedBy: 'outgoingConnections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?QuestNode $sourceNode = null;

    #[ORM\ManyToOne(targetEntity: QuestNode::class, inversedBy: 'incomingConnections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?QuestNode $targetNode = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $conditions = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceNode(): ?QuestNode
    {
        return $this->sourceNode;
    }

    public function setSourceNode(?QuestNode $sourceNode): self
    {
        $this->sourceNode = $sourceNode;
        return $this;
    }

    public function getTargetNode(): ?QuestNode
    {
        return $this->targetNode;
    }

    public function setTargetNode(?QuestNode $targetNode): self
    {
        $this->targetNode = $targetNode;
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
}
