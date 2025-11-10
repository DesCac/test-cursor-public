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
        $qStartNode->setText('Greetings, hero. I have a quest for you.');
        $qStartNode->setPositionX(100.0);
        $qStartNode->setPositionY(100.0);
        $manager->persist($qStartNode);

        $qInfoNode = new DialogNode();
        $qInfoNode->setNpc($questGiver);
        $qInfoNode->setNodeType('dialog');
        $qInfoNode->setText('The ancient ruins to the north are filled with monsters. Can you help?');
        $qInfoNode->setPositionX(100.0);
        $qInfoNode->setPositionY(200.0);
        $qInfoNode->setConditions(['level' => 5]);
        $manager->persist($qInfoNode);

        $qAcceptNode = new DialogNode();
        $qAcceptNode->setNpc($questGiver);
        $qAcceptNode->setNodeType('action');
        $qAcceptNode->setText('Thank you, brave hero! May fortune favor you.');
        $qAcceptNode->setPositionX(50.0);
        $qAcceptNode->setPositionY(300.0);
        $manager->persist($qAcceptNode);

        $qDeclineNode = new DialogNode();
        $qDeclineNode->setNpc($questGiver);
        $qDeclineNode->setNodeType('end');
        $qDeclineNode->setText('I understand. Come back if you change your mind.');
        $qDeclineNode->setPositionX(150.0);
        $qDeclineNode->setPositionY(300.0);
        $manager->persist($qDeclineNode);

        $qConn1 = new DialogConnection();
        $qConn1->setSourceNode($qStartNode);
        $qConn1->setTargetNode($qInfoNode);
        $manager->persist($qConn1);

        $qConn2 = new DialogConnection();
        $qConn2->setSourceNode($qInfoNode);
        $qConn2->setTargetNode($qAcceptNode);
        $qConn2->setChoiceText('I accept this quest');
        $manager->persist($qConn2);

        $qConn3 = new DialogConnection();
        $qConn3->setSourceNode($qInfoNode);
        $qConn3->setTargetNode($qDeclineNode);
        $qConn3->setChoiceText('Not right now');
        $manager->persist($qConn3);

        $manager->flush();
    }
}
