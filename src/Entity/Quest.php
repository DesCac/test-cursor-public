<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quests')]
class Quest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $objectives = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rewards = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $requirements = null;

    /** @var Collection<int, QuestNode> */
    #[ORM\OneToMany(targetEntity: QuestNode::class, mappedBy: 'quest', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $logicNodes;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->logicNodes = new ArrayCollection();
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

    public function getObjectives(): ?array
    {
        return $this->objectives;
    }

    public function setObjectives(?array $objectives): self
    {
        $this->objectives = $objectives;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRewards(): ?array
    {
        return $this->rewards;
    }

    public function setRewards(?array $rewards): self
    {
        $this->rewards = $rewards;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRequirements(): ?array
    {
        return $this->requirements;
    }

    public function setRequirements(?array $requirements): self
    {
        $this->requirements = $requirements;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, QuestNode>
     */
    public function getLogicNodes(): Collection
    {
        return $this->logicNodes;
    }

    public function addLogicNode(QuestNode $logicNode): self
    {
        if (!$this->logicNodes->contains($logicNode)) {
            $this->logicNodes->add($logicNode);
            $logicNode->setQuest($this);
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function removeLogicNode(QuestNode $logicNode): self
    {
        if ($this->logicNodes->removeElement($logicNode)) {
            if ($logicNode->getQuest() === $this) {
                $logicNode->setQuest(null);
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
