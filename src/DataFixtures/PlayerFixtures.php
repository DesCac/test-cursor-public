<?php

namespace App\DataFixtures;

use App\Entity\CharacterSkill;
use App\Entity\Player;
use App\Entity\PlayerCharacter;
use App\Entity\Quest;
use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlayerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $player = new Player('tg_user_12345', 'Aelwyn');

        /** @var Skill $arcanePulse */
        $arcanePulse = $this->getReference(SkillFixtures::SKILL_ARCANE_PULSE);
        $fireballMastery = $this->getReference(SkillFixtures::SKILL_FIREBALL);
        $shadowStep = $this->getReference(SkillFixtures::SKILL_SHADOW_STEP);
        $silentBlade = $this->getReference(SkillFixtures::SKILL_SILENT_BLADE);

        /** @var Quest|null $collectHerbsQuest */
        $collectHerbsQuest = $manager->getRepository(Quest::class)->findOneBy(['name' => 'Collect Herbs']);

        $mageClass = $this->getReference(PlayerClassFixtures::CLASS_MAGE);
        $thiefClass = $this->getReference(PlayerClassFixtures::CLASS_THIEF);

        $mageCharacter = new PlayerCharacter('Ария Эмберуив', $mageClass);
        $mageCharacter->setLevel(7)
            ->setExperience(1650)
            ->setAttributes([
                'intellect' => 9,
                'agility' => 5,
                'stamina' => 6,
            ]);

        $mageSkill1 = (new CharacterSkill())
            ->setSkill($arcanePulse)
            ->setUnlockContext(['source' => 'fixture']);
        $mageCharacter->addSkill($mageSkill1);

        $mageSkill2 = (new CharacterSkill())
            ->setSkill($fireballMastery)
            ->setUnlockContext(['source' => 'fixture']);
        $mageCharacter->addSkill($mageSkill2);

        $thiefCharacter = new PlayerCharacter('Шейд Шепот', $thiefClass);
        $thiefCharacter->setLevel(6)
            ->setExperience(1410)
            ->setAttributes([
                'agility' => 9,
                'luck' => 4,
                'perception' => 7,
            ]);

        if ($collectHerbsQuest instanceof Quest) {
            $thiefCharacter->addCompletedQuest($collectHerbsQuest);
        }

        $thiefSkill1 = (new CharacterSkill())
            ->setSkill($shadowStep)
            ->setUnlockContext(['source' => 'fixture']);
        $thiefCharacter->addSkill($thiefSkill1);

        $thiefSkill2 = (new CharacterSkill())
            ->setSkill($silentBlade)
            ->setUnlockContext(['source' => 'fixture']);
        $thiefCharacter->addSkill($thiefSkill2);

        $player->addCharacter($mageCharacter);
        $player->addCharacter($thiefCharacter);

        $manager->persist($player);
        $manager->persist($mageCharacter);
        $manager->persist($thiefCharacter);
        $manager->persist($mageSkill1);
        $manager->persist($mageSkill2);
        $manager->persist($thiefSkill1);
        $manager->persist($thiefSkill2);

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            PlayerClassFixtures::class,
            SkillFixtures::class,
            QuestFixtures::class,
        ];
    }
}

