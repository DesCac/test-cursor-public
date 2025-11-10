<?php

namespace App\Tests\Service;

use App\Entity\DialogConnection;
use App\Entity\DialogNode;
use App\Entity\NPC;
use App\Service\DialogValidationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DialogValidationServiceTest extends TestCase
{
    public function testValidateChoiceWithNonExistentNPC(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = new DialogValidationService($entityManager);

        $result = $service->validateChoice(999, 1, 1);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('NPC not found', $result['message']);
    }

    public function testValidateChoiceReturnsValidStructure(): void
    {
        $npc = new NPC();
        $node = new DialogNode();
        $connection = new DialogConnection();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = new DialogValidationService($entityManager);

        $result = $service->validateChoice(1, 1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('nextNodeId', $result);
        $this->assertIsBool($result['valid']);
    }
}
