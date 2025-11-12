<?php

namespace App\Tests\Service;

use App\Entity\CharacterSkill;
use App\Entity\Player;
use App\Entity\PlayerCharacter;
use App\Entity\PlayerClass;
use App\Entity\Skill;
use App\Service\SkillAvailabilityService;
use PHPUnit\Framework\TestCase;

class SkillAvailabilityServiceTest extends TestCase
{
    public function testSkillUnlocksWhenConditionsMet(): void
    {
        $service = new SkillAvailabilityService();

        $mage = (new PlayerClass())->setName('Маг');
        $fireMage = (new PlayerClass())->setName('Маг огня')->setParent($mage);

        $baseSkill = (new Skill())->setName('Магический заряд')->setTier('core');
        $baseSkill->addRequiredClass($mage);

        $advancedSkill = (new Skill())
            ->setName('Пламенный всплеск')
            ->setTier('specialized')
            ->setRequiredLevel(5);
        $advancedSkill->addRequiredClass($fireMage);
        $advancedSkill->addPrerequisiteSkill($baseSkill);

        $player = (new Player())->setTgUserId('tg-user');

        $character = (new PlayerCharacter())
            ->setPlayer($player)
            ->setClass($fireMage)
            ->setName('Элинор')
            ->setLevel(6);

        $characterSkill = new CharacterSkill();
        $characterSkill->setCharacter($character)->setSkill($baseSkill);

        $result = $service->evaluateUnlock($advancedSkill, $character);

        $this->assertTrue($result['canUnlock']);
        $this->assertFalse($result['alreadyUnlocked']);
        $this->assertSame([], $result['reasons']);
    }

    public function testSkillFailsWhenPrerequisitesMissing(): void
    {
        $service = new SkillAvailabilityService();

        $rogue = (new PlayerClass())->setName('Воришка');
        $assassin = (new PlayerClass())->setName('Ассасин')->setParent($rogue);

        $stealth = (new Skill())->setName('Шаг в тени')->setTier('core');
        $stealth->addRequiredClass($rogue);

        $silentStrike = (new Skill())
            ->setName('Бесшумный удар')
            ->setTier('advanced');
        $silentStrike->addRequiredClass($assassin);
        $silentStrike->addPrerequisiteSkill($stealth);

        $player = (new Player())->setTgUserId('tg-user-2');

        $character = (new PlayerCharacter())
            ->setPlayer($player)
            ->setClass($assassin)
            ->setName('Лисса')
            ->setLevel(5);

        $result = $service->evaluateUnlock($silentStrike, $character);

        $this->assertFalse($result['canUnlock']);
        $this->assertFalse($result['alreadyUnlocked']);
        $this->assertNotEmpty($result['reasons']);
        $this->assertStringContainsString('родительские скиллы', $result['reasons'][0]);
    }

    public function testAlreadyUnlockedFlag(): void
    {
        $service = new SkillAvailabilityService();

        $warrior = (new PlayerClass())->setName('Воин');
        $skill = (new Skill())->setName('Стойка копейщика')->setTier('core');
        $skill->addRequiredClass($warrior);

        $player = (new Player())->setTgUserId('tg-user-3');

        $character = (new PlayerCharacter())
            ->setPlayer($player)
            ->setClass($warrior)
            ->setName('Роран')
            ->setLevel(10);

        $characterSkill = new CharacterSkill();
        $characterSkill->setCharacter($character)->setSkill($skill);

        $result = $service->evaluateUnlock($skill, $character);

        $this->assertFalse($result['canUnlock']);
        $this->assertTrue($result['alreadyUnlocked']);
        $this->assertNotEmpty($result['reasons']);
        $this->assertStringContainsString('уже открыт', $result['reasons'][0]);
    }
}

