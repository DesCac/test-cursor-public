<?php

namespace App\DataFixtures;

use App\Entity\CharacterSkill;
use App\Entity\Player;
use App\Entity\PlayerCharacter;
use App\Entity\PlayerClass;
use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlayerSkillFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var array<string, PlayerClass> $classes */
        $classes = [];

        $classes['mage'] = (new PlayerClass())
            ->setName('Маг')
            ->setDescription('Базовый владелец тайной магии.');
        $classes['fire_mage'] = (new PlayerClass())
            ->setName('Маг огня')
            ->setDescription('Специалист по разрушительной магии пламени.');
        $classes['air_mage'] = (new PlayerClass())
            ->setName('Маг воздуха')
            ->setDescription('Повелитель ветра и молний.');
        $classes['warrior'] = (new PlayerClass())
            ->setName('Воин')
            ->setDescription('Фундаментальный ближний боец.');
        $classes['knight'] = (new PlayerClass())
            ->setName('Рыцарь')
            ->setDescription('Защитник с мечом и щитом.');
        $classes['heavy_knight'] = (new PlayerClass())
            ->setName('Тяжелый рыцарь')
            ->setDescription('Неуязвимая крепость в тяжелой броне.');
        $classes['commander'] = (new PlayerClass())
            ->setName('Коммандир')
            ->setDescription('Лидер, усиливающий союзников.');
        $classes['rogue'] = (new PlayerClass())
            ->setName('Воришка')
            ->setDescription('Мастер скрытности и ловкости.');
        $classes['assassin'] = (new PlayerClass())
            ->setName('Ассасин')
            ->setDescription('Искусный убийца из теней.');
        $classes['runner'] = (new PlayerClass())
            ->setName('Беглец')
            ->setDescription('Идеален для отвлечения и выживания.');

        foreach ($classes as $class) {
            $manager->persist($class);
        }

        // Establish inheritance
        $classes['fire_mage']->setParent($classes['mage']);
        $classes['air_mage']->setParent($classes['mage']);
        $classes['knight']->setParent($classes['warrior']);
        $classes['heavy_knight']->setParent($classes['knight']);
        $classes['commander']->setParent($classes['warrior']);
        $classes['assassin']->setParent($classes['rogue']);
        $classes['runner']->setParent($classes['rogue']);

        /** @var array<string, Skill> $skills */
        $skills = [];

        $skills['arcane_bolt'] = (new Skill())
            ->setName('Магический заряд')
            ->setTier('core')
            ->setDescription('Базовое заклинание мага, наносящее чистый урон.')
            ->setRequiredLevel(1)
            ->setMetadata(['damage' => 40, 'type' => 'arcane'])
            ->setPositionX(0)
            ->setPositionY(0)
            ->addRequiredClass($classes['mage']);

        $skills['flame_surge'] = (new Skill())
            ->setName('Пламенный всплеск')
            ->setTier('specialized')
            ->setDescription('Конусообразная волна огня, поджигающая врагов.')
            ->setRequiredLevel(4)
            ->setMetadata(['damage' => 90, 'status' => 'burning'])
            ->setPositionX(220)
            ->setPositionY(-120)
            ->addRequiredClass($classes['fire_mage'])
            ->addPrerequisiteSkill($skills['arcane_bolt']);

        $skills['wind_barrier'] = (new Skill())
            ->setName('Барьер ветра')
            ->setTier('specialized')
            ->setDescription('Создаёт воздушную защиту, снижающую урон по союзникам.')
            ->setRequiredLevel(4)
            ->setMetadata(['reduction' => 0.25, 'duration' => 6])
            ->setPositionX(220)
            ->setPositionY(120)
            ->addRequiredClass($classes['air_mage'])
            ->addPrerequisiteSkill($skills['arcane_bolt']);

        $skills['sword_mastery'] = (new Skill())
            ->setName('Мастерство меча')
            ->setTier('core')
            ->setDescription('Повышает базовый урон мечом и шанс парирования.')
            ->setRequiredLevel(1)
            ->setMetadata(['bonusDamage' => 12, 'parryChance' => 0.1])
            ->setPositionX(-260)
            ->setPositionY(-40)
            ->addRequiredClass($classes['warrior']);

        $skills['shield_wall'] = (new Skill())
            ->setName('Стена щитов')
            ->setTier('advanced')
            ->setDescription('Временно удваивает броню рыцаря.')
            ->setRequiredLevel(5)
            ->setMetadata(['armorMultiplier' => 2, 'duration' => 5])
            ->setPositionX(-40)
            ->setPositionY(-220)
            ->addRequiredClass($classes['knight'])
            ->addPrerequisiteSkill($skills['sword_mastery']);

        $skills['battle_order'] = (new Skill())
            ->setName('Боевой приказ')
            ->setTier('advanced')
            ->setDescription('Коммандир вдохновляет союзников, увеличивая их урон.')
            ->setRequiredLevel(5)
            ->setMetadata(['buff' => ['damageMultiplier' => 1.15, 'duration' => 8]])
            ->setPositionX(-40)
            ->setPositionY(120)
            ->addRequiredClass($classes['commander'])
            ->addPrerequisiteSkill($skills['sword_mastery'])
            ->addRequiredQuest($this->getReference('quest.collect_herbs'));

        $skills['fortress_mode'] = (new Skill())
            ->setName('Режим крепости')
            ->setTier('ultimate')
            ->setDescription('Тяжелый рыцарь становится почти неуязвим.')
            ->setRequiredLevel(8)
            ->setExtraRequirements(['cooldown' => 180, 'requiresShieldEquipped' => true])
            ->setMetadata(['damageReduction' => 0.7, 'duration' => 6])
            ->setPositionX(160)
            ->setPositionY(-340)
            ->addRequiredClass($classes['heavy_knight'])
            ->addPrerequisiteSkill($skills['shield_wall']);

        $skills['shadow_step'] = (new Skill())
            ->setName('Шаг в тени')
            ->setTier('core')
            ->setDescription('Воришка мгновенно перемещается за спину противника.')
            ->setRequiredLevel(1)
            ->setMetadata(['range' => 12, 'givesStealth' => true])
            ->setPositionX(-260)
            ->setPositionY(220)
            ->addRequiredClass($classes['rogue']);

        $skills['silent_strike'] = (new Skill())
            ->setName('Бесшумный удар')
            ->setTier('advanced')
            ->setDescription('Ассасин наносит критический удар из невидимости.')
            ->setRequiredLevel(4)
            ->setMetadata(['criticalMultiplier' => 2.5])
            ->setPositionX(-40)
            ->setPositionY(320)
            ->addRequiredClass($classes['assassin'])
            ->addPrerequisiteSkill($skills['shadow_step']);

        $skills['evasive_dash'] = (new Skill())
            ->setName('Маневренный рывок')
            ->setTier('advanced')
            ->setDescription('Беглец получает мгновенный рывок и шанс уклонения.')
            ->setRequiredLevel(3)
            ->setMetadata(['dashRange' => 15, 'dodgeBonus' => 0.2])
            ->setPositionX(-40)
            ->setPositionY(500)
            ->addRequiredClass($classes['runner'])
            ->addPrerequisiteSkill($skills['shadow_step']);

        $skills['shadow_assault'] = (new Skill())
            ->setName('Теневое наступление')
            ->setTier('ultimate')
            ->setDescription('Комбо-атака, требующая мастерства двух веток вора.')
            ->setRequiredLevel(6)
            ->setExtraRequirements(['requiresDualDaggers' => true])
            ->setMetadata(['hits' => 3, 'eachHitMultiplier' => 1.4])
            ->setPositionX(180)
            ->setPositionY(420)
            ->addRequiredClass($classes['assassin'])
            ->addRequiredClass($classes['runner'])
            ->addPrerequisiteSkill($skills['silent_strike'])
            ->addPrerequisiteSkill($skills['evasive_dash'])
            ->addRequiredQuest($this->getReference('quest.main_ruins'));

        foreach ($skills as $skill) {
            $manager->persist($skill);
        }

        // Player and characters
        $player = (new Player())
            ->setTgUserId('tg_user_hero_001')
            ->setDisplayName('Сафина');
        $manager->persist($player);

        $mageCharacter = (new PlayerCharacter())
            ->setPlayer($player)
            ->setClass($classes['mage'])
            ->setName('Элинор Файрвейл')
            ->setLevel(7)
            ->setExperience(2450)
            ->addCompletedQuest($this->getReference('quest.main_ruins'));
        $manager->persist($mageCharacter);

        $rogueCharacter = (new PlayerCharacter())
            ->setPlayer($player)
            ->setClass($classes['rogue'])
            ->setName('Лисса Теневест')
            ->setLevel(5)
            ->setExperience(1780)
            ->addCompletedQuest($this->getReference('quest.collect_herbs'));
        $manager->persist($rogueCharacter);

        // Unlocked skills
        $mageUnlocked = new CharacterSkill();
        $mageUnlocked->setCharacter($mageCharacter)->setSkill($skills['arcane_bolt']);
        $manager->persist($mageUnlocked);

        $mageFlame = new CharacterSkill();
        $mageFlame->setCharacter($mageCharacter)->setSkill($skills['flame_surge']);
        $manager->persist($mageFlame);

        $rogueShadowStep = new CharacterSkill();
        $rogueShadowStep->setCharacter($rogueCharacter)->setSkill($skills['shadow_step']);
        $manager->persist($rogueShadowStep);

        $rogueSilentStrike = new CharacterSkill();
        $rogueSilentStrike->setCharacter($rogueCharacter)->setSkill($skills['silent_strike']);
        $manager->persist($rogueSilentStrike);

        $manager->flush();
    }

    /**
     * @return array<class-string>
     */
    public function getDependencies(): array
    {
        return [
            QuestFixtures::class,
        ];
    }
}

