<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'dialog_connections')]
class DialogConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DialogNode::class, inversedBy: 'outgoingConnections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DialogNode $sourceNode = null;

    #[ORM\ManyToOne(targetEntity: DialogNode::class, inversedBy: 'incomingConnections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DialogNode $targetNode = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $choiceText = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $conditions = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceNode(): ?DialogNode
    {
        return $this->sourceNode;
    }

    public function setSourceNode(?DialogNode $sourceNode): self
    {
        $this->sourceNode = $sourceNode;
        return $this;
    }

    public function getTargetNode(): ?DialogNode
    {
        return $this->targetNode;
    }

    public function setTargetNode(?DialogNode $targetNode): self
    {
        $this->targetNode = $targetNode;
        return $this;
    }

    public function getChoiceText(): ?string
    {
        return $this->choiceText;
    }

    public function setChoiceText(?string $choiceText): self
    {
        $this->choiceText = $choiceText;
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
}
