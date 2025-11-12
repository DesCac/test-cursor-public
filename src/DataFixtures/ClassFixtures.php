<?php

namespace App\DataFixtures;

use App\Entity\CharacterClass;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ClassFixtures extends Fixture
{
    public const MAGE = 'class_mage';
    public const FIRE_MAGE = 'class_fire_mage';
    public const AIR_MAGE = 'class_air_mage';
    public const WARRIOR = 'class_warrior';
    public const KNIGHT = 'class_knight';
    public const COMMANDER = 'class_commander';
    public const HEAVY_KNIGHT = 'class_heavy_knight';
    public const ROGUE = 'class_rogue';
    public const ASSASSIN = 'class_assassin';
    public const RUNNER = 'class_runner';

    public function load(ObjectManager $manager): void
    {
        // Root class: Mage
        $mage = new CharacterClass();
        $mage->setName('Маг');
        $mage->setDescription('Владеет магией стихий и древними заклинаниями');
        $mage->setRequirements(['level' => ['min' => 1]]);
        $manager->persist($mage);
        $this->addReference(self::MAGE, $mage);

        // Mage children
        $fireMage = new CharacterClass();
        $fireMage->setName('Маг огня');
        $fireMage->setDescription('Специализируется на огненной магии, наносит большой урон');
        $fireMage->setParent($mage);
        $fireMage->setRequirements(['level' => ['min' => 10], 'quests' => ['completed' => [1]]]);
        $manager->persist($fireMage);
        $this->addReference(self::FIRE_MAGE, $fireMage);

        $airMage = new CharacterClass();
        $airMage->setName('Маг воздуха');
        $airMage->setDescription('Управляет ветром и молниями, быстр и уклончив');
        $airMage->setParent($mage);
        $airMage->setRequirements(['level' => ['min' => 10], 'quests' => ['completed' => [2]]]);
        $manager->persist($airMage);
        $this->addReference(self::AIR_MAGE, $airMage);

        // Root class: Warrior
        $warrior = new CharacterClass();
        $warrior->setName('Воин');
        $warrior->setDescription('Мастер ближнего боя, силён и вынослив');
        $warrior->setRequirements(['level' => ['min' => 1]]);
        $manager->persist($warrior);
        $this->addReference(self::WARRIOR, $warrior);

        // Warrior children
        $knight = new CharacterClass();
        $knight->setName('Рыцарь');
        $knight->setDescription('Защитник с тяжёлой бронёй и щитом');
        $knight->setParent($warrior);
        $knight->setRequirements(['level' => ['min' => 10], 'quests' => ['completed' => [3]]]);
        $manager->persist($knight);
        $this->addReference(self::KNIGHT, $knight);

        $commander = new CharacterClass();
        $commander->setName('Командир');
        $commander->setDescription('Лидер, вдохновляющий союзников на подвиги');
        $commander->setParent($warrior);
        $commander->setRequirements(['level' => ['min' => 12], 'quests' => ['completed' => [4]]]);
        $manager->persist($commander);
        $this->addReference(self::COMMANDER, $commander);

        // Knight child
        $heavyKnight = new CharacterClass();
        $heavyKnight->setName('Тяжёлый рыцарь');
        $heavyKnight->setDescription('Непробиваемый танк в тяжелейших доспехах');
        $heavyKnight->setParent($knight);
        $heavyKnight->setRequirements(['level' => ['min' => 20], 'quests' => ['completed' => [5]]]);
        $manager->persist($heavyKnight);
        $this->addReference(self::HEAVY_KNIGHT, $heavyKnight);

        // Root class: Rogue
        $rogue = new CharacterClass();
        $rogue->setName('Воришка');
        $rogue->setDescription('Ловкий и скрытный, мастер краж и уклонений');
        $rogue->setRequirements(['level' => ['min' => 1]]);
        $manager->persist($rogue);
        $this->addReference(self::ROGUE, $rogue);

        // Rogue children
        $assassin = new CharacterClass();
        $assassin->setName('Ассасин');
        $assassin->setDescription('Мастер скрытных убийств, критический урон');
        $assassin->setParent($rogue);
        $assassin->setRequirements(['level' => ['min' => 10], 'quests' => ['completed' => [6]]]);
        $manager->persist($assassin);
        $this->addReference(self::ASSASSIN, $assassin);

        $runner = new CharacterClass();
        $runner->setName('Беглец');
        $runner->setDescription('Невероятно быстр, уходит от любой опасности');
        $runner->setParent($rogue);
        $runner->setRequirements(['level' => ['min' => 10], 'quests' => ['completed' => [7]]]);
        $manager->persist($runner);
        $this->addReference(self::RUNNER, $runner);

        $manager->flush();
    }
}
