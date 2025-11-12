<?php

namespace App\DataFixtures;

use App\Entity\PlayerClass;
use App\Entity\Quest;
use App\Entity\Skill;
use App\Entity\SkillLink;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture implements DependentFixtureInterface
{
    public const SKILL_ARCANE_PULSE = 'skill.arcane_pulse';
    public const SKILL_FIREBALL = 'skill.fireball_mastery';
    public const SKILL_WIND_BLADE = 'skill.wind_blade';
    public const SKILL_SHIELD_BASH = 'skill.shield_bash';
    public const SKILL_KNIGHT_RESOLVE = 'skill.knight_resolve';
    public const SKILL_COMMANDERS_RALLY = 'skill.commanders_rally';
    public const SKILL_IRON_BULWARK = 'skill.iron_bulwark';
    public const SKILL_SHADOW_STEP = 'skill.shadow_step';
    public const SKILL_SILENT_BLADE = 'skill.silent_blade';
    public const SKILL_EVASIVE_DASH = 'skill.evasive_dash';
    public const SKILL_MASTER_GAMBIT = 'skill.master_gambit';

    public function load(ObjectManager $manager): void
    {
        /** @var PlayerClass $mage */
        $mage = $this->getReference(PlayerClassFixtures::CLASS_MAGE);
        $mageFire = $this->getReference(PlayerClassFixtures::CLASS_MAGE_FIRE);
        $mageAir = $this->getReference(PlayerClassFixtures::CLASS_MAGE_AIR);
        $warrior = $this->getReference(PlayerClassFixtures::CLASS_WARRIOR);
        $knight = $this->getReference(PlayerClassFixtures::CLASS_KNIGHT);
        $commander = $this->getReference(PlayerClassFixtures::CLASS_COMMANDER);
        $heavyKnight = $this->getReference(PlayerClassFixtures::CLASS_HEAVY_KNIGHT);
        $thief = $this->getReference(PlayerClassFixtures::CLASS_THIEF);
        $assassin = $this->getReference(PlayerClassFixtures::CLASS_ASSASSIN);
        $escapeArtist = $this->getReference(PlayerClassFixtures::CLASS_ESCAPE_ARTIST);

        $quests = $manager->getRepository(Quest::class)->findBy([], ['id' => 'ASC']);
        $collectHerbsQuest = null;
        foreach ($quests as $quest) {
            if ($quest->getName() === 'Collect Herbs') {
                $collectHerbsQuest = $quest;
            }
        }

        $skills = [];

        $skills[self::SKILL_ARCANE_PULSE] = (new Skill('Арканный импульс', 'arcane-pulse'))
            ->setDescription('Базовое заклинание, усиливающее магическую связь с элементами.')
            ->setRequiredLevel(1)
            ->setPositionX(-300)
            ->setPositionY(-100)
            ->setAvailabilityRules(null)
            ->addRequiredClass($mage);

        $skills[self::SKILL_FIREBALL] = (new Skill('Повелитель огня', 'fireball-mastery'))
            ->setDescription('Мощный огненный залп, оставляющий на земле пламя.')
            ->setRequiredLevel(5)
            ->setPositionX(-150)
            ->setPositionY(40)
            ->addRequiredClass($mageFire);

        $skills[self::SKILL_WIND_BLADE] = (new Skill('Клинок ветра', 'wind-blade'))
            ->setDescription('Разрезает врагов острым потоком воздуха и молний.')
            ->setRequiredLevel(5)
            ->setPositionX(-450)
            ->setPositionY(50)
            ->addRequiredClass($mageAir);

        $skills[self::SKILL_SHIELD_BASH] = (new Skill('Удар щитом', 'shield-bash'))
            ->setDescription('Мощный удар, оглушающий противников.')
            ->setRequiredLevel(1)
            ->setPositionX(100)
            ->setPositionY(-120)
            ->addRequiredClass($warrior);

        $skills[self::SKILL_KNIGHT_RESOLVE] = (new Skill('Стойкость рыцаря', 'knight-resolve'))
            ->setDescription('Повышает сопротивление урону и регенерацию.')
            ->setRequiredLevel(4)
            ->setPositionX(250)
            ->setPositionY(40)
            ->addRequiredClass($knight);

        $skills[self::SKILL_COMMANDERS_RALLY] = (new Skill('Боевой клич командира', 'commanders-rally'))
            ->setDescription('Вдохновляет союзников, повышая их урон.')
            ->setRequiredLevel(6)
            ->setPositionX(-50)
            ->setPositionY(40)
            ->addRequiredClass($commander);

        $skills[self::SKILL_IRON_BULWARK] = (new Skill('Железный бастион', 'iron-bulwark'))
            ->setDescription('Временно делает носителя практически неуязвимым.')
            ->setRequiredLevel(8)
            ->setPositionX(330)
            ->setPositionY(220)
            ->addRequiredClass($heavyKnight);

        $skills[self::SKILL_SHADOW_STEP] = (new Skill('Теневой шаг', 'shadow-step'))
            ->setDescription('Телепортирует в спину врага и повышает уклонение.')
            ->setRequiredLevel(2)
            ->setPositionX(-100)
            ->setPositionY(-260)
            ->addRequiredClass($thief);

        $skills[self::SKILL_SILENT_BLADE] = (new Skill('Безмолвный клинок', 'silent-blade'))
            ->setDescription('Позволяет наносить критические удары из невидимости.')
            ->setRequiredLevel(5)
            ->setPositionX(-240)
            ->setPositionY(-40)
            ->addRequiredClass($assassin);

        $skills[self::SKILL_EVASIVE_DASH] = (new Skill('Побег сквозь тени', 'evasive-dash'))
            ->setDescription('Рывок, оставляющий фантомов и снимающий контроль.')
            ->setRequiredLevel(5)
            ->setPositionX(40)
            ->setPositionY(-40)
            ->addRequiredClass($escapeArtist);

        $skills[self::SKILL_MASTER_GAMBIT] = (new Skill('Рискованный гамбит', 'master-gambit'))
            ->setDescription('Серия убийственных атак, доступная мастерам скрытности.')
            ->setRequiredLevel(9)
            ->setPositionX(-100)
            ->setPositionY(140)
            ->setAvailabilityRules([
                'attributes' => [
                    'agility' => ['min' => 8],
                ],
            ])
            ->addRequiredClass($assassin)
            ->addRequiredClass($escapeArtist);

        if ($collectHerbsQuest instanceof Quest) {
            $skills[self::SKILL_MASTER_GAMBIT]->addRequiredQuest($collectHerbsQuest);
        }

        foreach ($skills as $key => $skill) {
            $manager->persist($skill);
            $this->addReference($key, $skill);
        }

        $links = [];

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_ARCANE_PULSE])
            ->setChildSkill($skills[self::SKILL_FIREBALL])
            ->setRequiresAllParents(true);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_ARCANE_PULSE])
            ->setChildSkill($skills[self::SKILL_WIND_BLADE])
            ->setRequiresAllParents(true);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_SHIELD_BASH])
            ->setChildSkill($skills[self::SKILL_KNIGHT_RESOLVE])
            ->setRequiresAllParents(true);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_SHIELD_BASH])
            ->setChildSkill($skills[self::SKILL_COMMANDERS_RALLY])
            ->setRequiresAllParents(true);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_KNIGHT_RESOLVE])
            ->setChildSkill($skills[self::SKILL_IRON_BULWARK])
            ->setRequiresAllParents(true)
            ->setMetadata(['note' => 'Требуется усиленная броня']);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_SHADOW_STEP])
            ->setChildSkill($skills[self::SKILL_SILENT_BLADE])
            ->setRequiresAllParents(true);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_SHADOW_STEP])
            ->setChildSkill($skills[self::SKILL_EVASIVE_DASH])
            ->setRequiresAllParents(true);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_SILENT_BLADE])
            ->setChildSkill($skills[self::SKILL_MASTER_GAMBIT])
            ->setRequiresAllParents(true);

        $links[] = (new SkillLink())
            ->setParentSkill($skills[self::SKILL_EVASIVE_DASH])
            ->setChildSkill($skills[self::SKILL_MASTER_GAMBIT])
            ->setRequiresAllParents(true);

        foreach ($links as $link) {
            $manager->persist($link);
        }

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            PlayerClassFixtures::class,
            QuestFixtures::class,
        ];
    }
}

