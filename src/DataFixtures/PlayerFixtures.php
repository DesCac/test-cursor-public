<?php

namespace App\DataFixtures;

use App\Entity\Player;
use App\Entity\PlayerCharacter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlayerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Create test player
        $player = new Player();
        $player->setTgUserId('123456789');
        $player->setUsername('test_player');
        $manager->persist($player);

        // Get class references
        $mageClass = $this->getReference(ClassFixtures::MAGE);
        $rogueClass = $this->getReference(ClassFixtures::ROGUE);

        // Character 1: Mage
        $mageCharacter = new PlayerCharacter();
        $mageCharacter->setPlayer($player);
        $mageCharacter->setName('Альдрик Огненный');
        $mageCharacter->setLevel(15);
        $mageCharacter->setCharacterClass($mageClass);
        $mageCharacter->setCompletedQuestIds([1, 2]);
        $mageCharacter->setInventory([
            'potions' => [
                'health' => 5,
                'mana' => 10
            ],
            'gold' => 1500,
            'items' => [
                ['name' => 'Магический посох', 'type' => 'weapon', 'damage' => 50],
                ['name' => 'Мантия чародея', 'type' => 'armor', 'defense' => 30]
            ]
        ]);
        $manager->persist($mageCharacter);

        // Character 2: Rogue
        $rogueCharacter = new PlayerCharacter();
        $rogueCharacter->setPlayer($player);
        $rogueCharacter->setName('Тень');
        $rogueCharacter->setLevel(12);
        $rogueCharacter->setCharacterClass($rogueClass);
        $rogueCharacter->setCompletedQuestIds([6]);
        $rogueCharacter->setInventory([
            'potions' => [
                'health' => 8,
                'poison' => 5
            ],
            'gold' => 2300,
            'items' => [
                ['name' => 'Кинжалы близнецов', 'type' => 'weapon', 'damage' => 70],
                ['name' => 'Кожаные доспехи', 'type' => 'armor', 'defense' => 20]
            ]
        ]);
        $manager->persist($rogueCharacter);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ClassFixtures::class,
        ];
    }
}
