<?php

namespace App\DataFixtures;

use App\Entity\PlayerClass;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PlayerClassFixtures extends Fixture
{
    public const CLASS_MAGE = 'class.mage';
    public const CLASS_MAGE_FIRE = 'class.mage_fire';
    public const CLASS_MAGE_AIR = 'class.mage_air';
    public const CLASS_WARRIOR = 'class.warrior';
    public const CLASS_KNIGHT = 'class.knight';
    public const CLASS_COMMANDER = 'class.commander';
    public const CLASS_HEAVY_KNIGHT = 'class.heavy_knight';
    public const CLASS_THIEF = 'class.thief';
    public const CLASS_ASSASSIN = 'class.assassin';
    public const CLASS_ESCAPE_ARTIST = 'class.escape_artist';

    public function load(ObjectManager $manager): void
    {
        $classes = [];

        $classes[self::CLASS_MAGE] = (new PlayerClass('Маг', 'mage'))
            ->setDescription('Базовый класс магов, владеющих силой стихий и арканой.');
        $classes[self::CLASS_WARRIOR] = (new PlayerClass('Воин', 'warrior'))
            ->setDescription('Закалённый в битвах боец ближнего боя.');
        $classes[self::CLASS_THIEF] = (new PlayerClass('Воришка', 'thief'))
            ->setDescription('Мастер скрытности и ловкости.');

        $classes[self::CLASS_MAGE_FIRE] = (new PlayerClass('Маг огня', 'mage-fire'))
            ->setDescription('Специалист по разрушительным огненным заклинаниям.')
            ->setParent($classes[self::CLASS_MAGE]);
        $classes[self::CLASS_MAGE_AIR] = (new PlayerClass('Маг воздуха', 'mage-air'))
            ->setDescription('Контролирует поток воздуха и молний.')
            ->setParent($classes[self::CLASS_MAGE]);

        $classes[self::CLASS_KNIGHT] = (new PlayerClass('Рыцарь', 'knight'))
            ->setDescription('Благородный защитник с тяжёлым оружием.')
            ->setParent($classes[self::CLASS_WARRIOR]);
        $classes[self::CLASS_COMMANDER] = (new PlayerClass('Командир', 'commander'))
            ->setDescription('Военный стратег, вдохновляющий союзников.')
            ->setParent($classes[self::CLASS_WARRIOR]);
        $classes[self::CLASS_HEAVY_KNIGHT] = (new PlayerClass('Тяжёлый рыцарь', 'heavy-knight'))
            ->setDescription('Укреплённый танк, способный выдержать любой натиск.')
            ->setParent($classes[self::CLASS_KNIGHT]);

        $classes[self::CLASS_ASSASSIN] = (new PlayerClass('Ассасин', 'assassin'))
            ->setDescription('Искусный убийца, мастер незаметных атак.')
            ->setParent($classes[self::CLASS_THIEF]);
        $classes[self::CLASS_ESCAPE_ARTIST] = (new PlayerClass('Беглец', 'escape-artist'))
            ->setDescription('Непревзойдённый мастер уклонения и отступления.')
            ->setParent($classes[self::CLASS_THIEF]);

        foreach ($classes as $key => $class) {
            $manager->persist($class);
            $this->addReference($key, $class);
        }

        $manager->flush();
    }
}

