<?php

namespace App\DataFixtures;

use App\Entity\Quest;
use App\Entity\QuestConnection;
use App\Entity\QuestNode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class QuestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a main quest
        $mainQuest = new Quest();
        $mainQuest->setName('The Ancient Ruins');
        $mainQuest->setDescription('Explore the ancient ruins and defeat the boss');
        $mainQuest->setObjectives([
            'Find the entrance to the ruins',
            'Defeat 10 skeleton warriors',
            'Find the boss chamber',
            'Defeat the Ancient Guardian'
        ]);
        $mainQuest->setRewards([
            'gold' => 500,
            'experience' => 1000,
            'item' => 'Ancient Sword'
        ]);
        $mainQuest->setRequirements([
            'level' => 5,
            'quest_completed' => []
        ]);
        $manager->persist($mainQuest);

        // Create quest nodes
        $startNode = new QuestNode();
        $startNode->setQuest($mainQuest);
        $startNode->setNodeType('start');
        $startNode->setData(['description' => 'Quest accepted']);
        $startNode->setPositionX(100.0);
        $startNode->setPositionY(100.0);
        $manager->persist($startNode);

        $obj1Node = new QuestNode();
        $obj1Node->setQuest($mainQuest);
        $obj1Node->setNodeType('objective');
        $obj1Node->setData(['objective' => 'Find the entrance', 'location' => 'North Mountains']);
        $obj1Node->setPositionX(100.0);
        $obj1Node->setPositionY(200.0);
        $manager->persist($obj1Node);

        $obj2Node = new QuestNode();
        $obj2Node->setQuest($mainQuest);
        $obj2Node->setNodeType('objective');
        $obj2Node->setData(['objective' => 'Defeat skeletons', 'count' => 10]);
        $obj2Node->setPositionX(100.0);
        $obj2Node->setPositionY(300.0);
        $manager->persist($obj2Node);

        $condNode = new QuestNode();
        $condNode->setQuest($mainQuest);
        $condNode->setNodeType('condition');
        $condNode->setData(['check' => 'has_key']);
        $condNode->setConditions(['has_item' => 'ancient_key']);
        $condNode->setPositionX(100.0);
        $condNode->setPositionY(400.0);
        $manager->persist($condNode);

        $bossNode = new QuestNode();
        $bossNode->setQuest($mainQuest);
        $bossNode->setNodeType('objective');
        $bossNode->setData(['objective' => 'Defeat boss', 'boss' => 'Ancient Guardian']);
        $bossNode->setPositionX(100.0);
        $bossNode->setPositionY(500.0);
        $manager->persist($bossNode);

        $rewardNode = new QuestNode();
        $rewardNode->setQuest($mainQuest);
        $rewardNode->setNodeType('reward');
        $rewardNode->setData([
            'gold' => 500,
            'experience' => 1000,
            'item' => 'Ancient Sword'
        ]);
        $rewardNode->setPositionX(100.0);
        $rewardNode->setPositionY(600.0);
        $manager->persist($rewardNode);

        $endNode = new QuestNode();
        $endNode->setQuest($mainQuest);
        $endNode->setNodeType('end');
        $endNode->setData(['description' => 'Quest completed']);
        $endNode->setPositionX(100.0);
        $endNode->setPositionY(700.0);
        $manager->persist($endNode);

        // Create connections
        $conn1 = new QuestConnection();
        $conn1->setSourceNode($startNode);
        $conn1->setTargetNode($obj1Node);
        $manager->persist($conn1);

        $conn2 = new QuestConnection();
        $conn2->setSourceNode($obj1Node);
        $conn2->setTargetNode($obj2Node);
        $manager->persist($conn2);

        $conn3 = new QuestConnection();
        $conn3->setSourceNode($obj2Node);
        $conn3->setTargetNode($condNode);
        $manager->persist($conn3);

        $conn4 = new QuestConnection();
        $conn4->setSourceNode($condNode);
        $conn4->setTargetNode($bossNode);
        $manager->persist($conn4);

        $conn5 = new QuestConnection();
        $conn5->setSourceNode($bossNode);
        $conn5->setTargetNode($rewardNode);
        $manager->persist($conn5);

        $conn6 = new QuestConnection();
        $conn6->setSourceNode($rewardNode);
        $conn6->setTargetNode($endNode);
        $manager->persist($conn6);

        // Create a side quest
        $sideQuest = new Quest();
        $sideQuest->setName('Collect Herbs');
        $sideQuest->setDescription('Collect 5 healing herbs for the alchemist');
        $sideQuest->setObjectives([
            'Collect 5 healing herbs'
        ]);
        $sideQuest->setRewards([
            'gold' => 100,
            'experience' => 200
        ]);
        $sideQuest->setRequirements([
            'level' => 1
        ]);
        $manager->persist($sideQuest);

        $this->addReference('quest.main_ruins', $mainQuest);
        $this->addReference('quest.collect_herbs', $sideQuest);

        $manager->flush();
    }
}
