<?php

namespace App\DataFixtures;

use App\Entity\DialogConnection;
use App\Entity\DialogNode;
use App\Entity\NPC;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NPCFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a merchant NPC
        $merchant = new NPC();
        $merchant->setName('Merchant Bob');
        $merchant->setDescription('A friendly merchant who sells potions and equipment');
        $manager->persist($merchant);

        // Create dialog nodes for merchant
        $startNode = new DialogNode();
        $startNode->setNpc($merchant);
        $startNode->setNodeType('start');
        $startNode->setText('Welcome to my shop, traveler!');
        $startNode->setPositionX(100.0);
        $startNode->setPositionY(100.0);
        $manager->persist($startNode);

        $greetingNode = new DialogNode();
        $greetingNode->setNpc($merchant);
        $greetingNode->setNodeType('dialog');
        $greetingNode->setText('What can I help you with today?');
        $greetingNode->setPositionX(100.0);
        $greetingNode->setPositionY(200.0);
        $manager->persist($greetingNode);

        $buyNode = new DialogNode();
        $buyNode->setNpc($merchant);
        $buyNode->setNodeType('action');
        $buyNode->setText('Here are my wares. Take a look!');
        $buyNode->setPositionX(50.0);
        $buyNode->setPositionY(300.0);
        $manager->persist($buyNode);

        $sellNode = new DialogNode();
        $sellNode->setNpc($merchant);
        $sellNode->setNodeType('action');
        $sellNode->setText('Let me see what you have to sell.');
        $sellNode->setPositionX(150.0);
        $sellNode->setPositionY(300.0);
        $manager->persist($sellNode);

        $endNode = new DialogNode();
        $endNode->setNpc($merchant);
        $endNode->setNodeType('end');
        $endNode->setText('Come back anytime!');
        $endNode->setPositionX(100.0);
        $endNode->setPositionY(400.0);
        $manager->persist($endNode);

        // Create connections
        $conn1 = new DialogConnection();
        $conn1->setSourceNode($startNode);
        $conn1->setTargetNode($greetingNode);
        $manager->persist($conn1);

        $conn2 = new DialogConnection();
        $conn2->setSourceNode($greetingNode);
        $conn2->setTargetNode($buyNode);
        $conn2->setChoiceText('I want to buy something');
        $manager->persist($conn2);

        $conn3 = new DialogConnection();
        $conn3->setSourceNode($greetingNode);
        $conn3->setTargetNode($sellNode);
        $conn3->setChoiceText('I have items to sell');
        $manager->persist($conn3);

        $conn4 = new DialogConnection();
        $conn4->setSourceNode($buyNode);
        $conn4->setTargetNode($endNode);
        $manager->persist($conn4);

        $conn5 = new DialogConnection();
        $conn5->setSourceNode($sellNode);
        $conn5->setTargetNode($endNode);
        $manager->persist($conn5);

        // Create a quest giver NPC
        $questGiver = new NPC();
        $questGiver->setName('Elder Sage');
        $questGiver->setDescription('A wise old sage with an important quest');
        $manager->persist($questGiver);

        $qStartNode = new DialogNode();
        $qStartNode->setNpc($questGiver);
        $qStartNode->setNodeType('start');
        $qStartNode->setText('Greetings, hero. I have been watching your deeds from afar.');
        $qStartNode->setPositionX(120.0);
        $qStartNode->setPositionY(60.0);
        $manager->persist($qStartNode);

        $qIntroNode = new DialogNode();
        $qIntroNode->setNpc($questGiver);
        $qIntroNode->setNodeType('dialog');
        $qIntroNode->setText('The ancient ruins to the north have awakened. We must seal the astral breach before dusk.');
        $qIntroNode->setPositionX(120.0);
        $qIntroNode->setPositionY(180.0);
        $manager->persist($qIntroNode);

        $qChoiceNode = new DialogNode();
        $qChoiceNode->setNpc($questGiver);
        $qChoiceNode->setNodeType('choice');
        $qChoiceNode->setText('How will you respond to the Elder Sage?');
        $qChoiceNode->setPositionX(120.0);
        $qChoiceNode->setPositionY(320.0);
        $manager->persist($qChoiceNode);

        $qLoreNode = new DialogNode();
        $qLoreNode->setNpc($questGiver);
        $qLoreNode->setNodeType('dialog');
        $qLoreNode->setText('Legends speak of a lost moon shard that amplifies arcane wards. With it, the breach can be sealed for centuries.');
        $qLoreNode->setPositionX(360.0);
        $qLoreNode->setPositionY(320.0);
        $manager->persist($qLoreNode);

        $qTrialNode = new DialogNode();
        $qTrialNode->setNpc($questGiver);
        $qTrialNode->setNodeType('condition');
        $qTrialNode->setText('Let me gauge your readiness. Only those of experience and resolve may proceed.');
        $qTrialNode->setConditions([
            'requires' => [
                'level' => ['min' => 7],
                'reputation' => ['mages_guild' => ['min' => 5]],
            ],
            'flags' => [
                'quests' => [
                    'library_intro' => true,
                ],
            ],
        ]);
        $qTrialNode->setPositionX(200.0);
        $qTrialNode->setPositionY(470.0);
        $manager->persist($qTrialNode);

        $qAcceptNode = new DialogNode();
        $qAcceptNode->setNpc($questGiver);
        $qAcceptNode->setNodeType('action');
        $qAcceptNode->setText('Take this warding sigil. Place it within the breach once you defeat the guardian spirit.');
        $qAcceptNode->setPositionX(60.0);
        $qAcceptNode->setPositionY(620.0);
        $manager->persist($qAcceptNode);

        $qSpecialNode = new DialogNode();
        $qSpecialNode->setNpc($questGiver);
        $qSpecialNode->setNodeType('action');
        $qSpecialNode->setText('You already possess a moon shard? Extraordinary! I will attune it to resonate with the breach.');
        $qSpecialNode->setPositionX(240.0);
        $qSpecialNode->setPositionY(620.0);
        $manager->persist($qSpecialNode);

        $qRewardNode = new DialogNode();
        $qRewardNode->setNpc($questGiver);
        $qRewardNode->setNodeType('reward');
        $qRewardNode->setText('May this astral compass guide you. It will glow whenever a planar tear is near.');
        $qRewardNode->setPositionX(420.0);
        $qRewardNode->setPositionY(620.0);
        $manager->persist($qRewardNode);

        $qSecretNode = new DialogNode();
        $qSecretNode->setNpc($questGiver);
        $qSecretNode->setNodeType('dialog');
        $qSecretNode->setText('There is a hidden aqueduct beneath the ruins. Only allies of the shadow court know how to navigate it.');
        $qSecretNode->setConditions([
            'factions' => [
                'shadow_court' => 'ally',
            ],
        ]);
        $qSecretNode->setPositionX(580.0);
        $qSecretNode->setPositionY(470.0);
        $manager->persist($qSecretNode);

        $qDeclineNode = new DialogNode();
        $qDeclineNode->setNpc($questGiver);
        $qDeclineNode->setNodeType('end');
        $qDeclineNode->setText('I understand. The breach cannot wait forever, but you must follow your own path.');
        $qDeclineNode->setPositionX(620.0);
        $qDeclineNode->setPositionY(620.0);
        $manager->persist($qDeclineNode);

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qStartNode)
                ->setTargetNode($qIntroNode)
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qIntroNode)
                ->setTargetNode($qChoiceNode)
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qChoiceNode)
                ->setTargetNode($qTrialNode)
                ->setChoiceText('Я готов помочь, испытай меня')
                ->setConditions([
                    'reputation' => [
                        'mages_guild' => ['min' => 8],
                    ],
                ])
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qChoiceNode)
                ->setTargetNode($qLoreNode)
                ->setChoiceText('Сначала расскажи подробнее')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qChoiceNode)
                ->setTargetNode($qDeclineNode)
                ->setChoiceText('Сейчас не время для подвигов')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qLoreNode)
                ->setTargetNode($qTrialNode)
                ->setChoiceText('Я достану moon shard, будь спокоен')
                ->setConditions([
                    'flags' => [
                        'heard_about_shard' => true,
                    ],
                ])
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qLoreNode)
                ->setTargetNode($qSecretNode)
                ->setChoiceText('Я знаю о тайном пути от друзей из тени')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qLoreNode)
                ->setTargetNode($qDeclineNode)
                ->setChoiceText('Это слишком опасно для меня')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qTrialNode)
                ->setTargetNode($qAcceptNode)
                ->setChoiceText('Я достоин. Нужно лишь указание')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qTrialNode)
                ->setTargetNode($qSpecialNode)
                ->setChoiceText('У меня уже есть осколок луны')
                ->setConditions([
                    'inventory' => [
                        'moon_shard' => true,
                    ],
                ])
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qTrialNode)
                ->setTargetNode($qDeclineNode)
                ->setChoiceText('Я вернусь, когда буду сильнее')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qAcceptNode)
                ->setTargetNode($qRewardNode)
                ->setChoiceText('Я выполню задание — что дальше?')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qSpecialNode)
                ->setTargetNode($qRewardNode)
                ->setChoiceText('Прими этот осколок и усили защиту')
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qSecretNode)
                ->setTargetNode($qDeclineNode)
                ->setChoiceText('Тайный путь слишком рискован')
                ->setConditions([
                    'morality' => 'lawful',
                ])
        );

        $manager->persist(
            (new DialogConnection())
                ->setSourceNode($qRewardNode)
                ->setTargetNode($qDeclineNode)
                ->setChoiceText('До встречи, мудрец')
        );

        $manager->flush();
    }
}
