<?php

namespace App\DataFixtures;

use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get class references
        $mageClass = $this->getReference(ClassFixtures::MAGE);
        $fireMageClass = $this->getReference(ClassFixtures::FIRE_MAGE);
        $warriorClass = $this->getReference(ClassFixtures::WARRIOR);
        $rogueClass = $this->getReference(ClassFixtures::ROGUE);
        $assassinClass = $this->getReference(ClassFixtures::ASSASSIN);

        // === MAGE SKILLS ===
        
        // Basic mage skill
        $arcaneBlast = new Skill();
        $arcaneBlast->setName('Магический взрыв');
        $arcaneBlast->setDescription('Базовое заклинание мага, наносит магический урон');
        $arcaneBlast->setUnlockConditions(['class' => ['id' => $mageClass->getId()], 'level' => ['min' => 1]]);
        $arcaneBlast->setEffects(['damage' => 50, 'type' => 'magic']);
        $arcaneBlast->setPositionX(100);
        $arcaneBlast->setPositionY(100);
        $manager->persist($arcaneBlast);

        // Fire path
        $fireball = new Skill();
        $fireball->setName('Огненный шар');
        $fireball->setDescription('Мощное огненное заклинание');
        $fireball->setUnlockConditions(['class' => ['id' => $fireMageClass->getId()], 'level' => ['min' => 12]]);
        $fireball->setEffects(['damage' => 120, 'type' => 'fire', 'dot' => 20]);
        $fireball->setPositionX(200);
        $fireball->setPositionY(200);
        $fireball->addParent($arcaneBlast);
        $manager->persist($fireball);

        $inferno = new Skill();
        $inferno->setName('Инферно');
        $inferno->setDescription('Создаёт столп огня, наносящий огромный урон');
        $inferno->setUnlockConditions(['class' => ['id' => $fireMageClass->getId()], 'level' => ['min' => 20]]);
        $inferno->setEffects(['damage' => 300, 'type' => 'fire', 'aoe' => true]);
        $inferno->setPositionX(300);
        $inferno->setPositionY(300);
        $inferno->addParent($fireball);
        $manager->persist($inferno);

        // === WARRIOR SKILLS ===
        
        // Basic warrior skill
        $slash = new Skill();
        $slash->setName('Мощный удар');
        $slash->setDescription('Базовая атака воина');
        $slash->setUnlockConditions(['class' => ['id' => $warriorClass->getId()], 'level' => ['min' => 1]]);
        $slash->setEffects(['damage' => 80, 'type' => 'physical']);
        $slash->setPositionX(100);
        $slash->setPositionY(100);
        $manager->persist($slash);

        $charge = new Skill();
        $charge->setName('Рывок');
        $charge->setDescription('Быстро приближается к врагу и оглушает его');
        $charge->setUnlockConditions(['class' => ['id' => $warriorClass->getId()], 'level' => ['min' => 8]]);
        $charge->setEffects(['damage' => 60, 'stun' => 2]);
        $charge->setPositionX(200);
        $charge->setPositionY(200);
        $charge->addParent($slash);
        $manager->persist($charge);

        $whirlwind = new Skill();
        $whirlwind->setName('Вихрь клинков');
        $whirlwind->setDescription('Атакует всех врагов вокруг');
        $whirlwind->setUnlockConditions(['class' => ['id' => $warriorClass->getId()], 'level' => ['min' => 15]]);
        $whirlwind->setEffects(['damage' => 100, 'type' => 'physical', 'aoe' => true]);
        $whirlwind->setPositionX(300);
        $whirlwind->setPositionY(300);
        $whirlwind->addParent($charge);
        $manager->persist($whirlwind);

        // === ROGUE SKILLS ===
        
        // Basic rogue skill
        $backstab = new Skill();
        $backstab->setName('Удар в спину');
        $backstab->setDescription('Атака из тени с критическим уроном');
        $backstab->setUnlockConditions(['class' => ['id' => $rogueClass->getId()], 'level' => ['min' => 1]]);
        $backstab->setEffects(['damage' => 100, 'type' => 'physical', 'crit' => true]);
        $backstab->setPositionX(100);
        $backstab->setPositionY(100);
        $manager->persist($backstab);

        $stealth = new Skill();
        $stealth->setName('Незаметность');
        $stealth->setDescription('Становится невидимым для врагов');
        $stealth->setUnlockConditions(['class' => ['id' => $rogueClass->getId()], 'level' => ['min' => 5]]);
        $stealth->setEffects(['duration' => 10, 'invisibility' => true]);
        $stealth->setPositionX(200);
        $stealth->setPositionY(150);
        $stealth->addParent($backstab);
        $manager->persist($stealth);

        $poisonBlade = new Skill();
        $poisonBlade->setName('Отравленное лезвие');
        $poisonBlade->setDescription('Наносит урон ядом со временем');
        $poisonBlade->setUnlockConditions(['class' => ['id' => $rogueClass->getId()], 'level' => ['min' => 7]]);
        $poisonBlade->setEffects(['damage' => 40, 'poison' => 60, 'duration' => 10]);
        $poisonBlade->setPositionX(200);
        $poisonBlade->setPositionY(250);
        $poisonBlade->addParent($backstab);
        $manager->persist($poisonBlade);

        // MULTI-PARENT SKILL - requires both stealth and poison blade
        $shadowAssassin = new Skill();
        $shadowAssassin->setName('Теневой убийца');
        $shadowAssassin->setDescription('Мастерский приём: невидимая атака с ядом и критическим уроном');
        $shadowAssassin->setUnlockConditions([
            'class' => ['id' => $assassinClass->getId()], 
            'level' => ['min' => 15],
            'quests' => ['completed' => [6]]
        ]);
        $shadowAssassin->setEffects([
            'damage' => 200, 
            'type' => 'physical', 
            'crit' => true, 
            'poison' => 100,
            'invisibility' => true
        ]);
        $shadowAssassin->setPositionX(350);
        $shadowAssassin->setPositionY(200);
        // This skill requires BOTH parent skills to be unlocked
        $shadowAssassin->addParent($stealth);
        $shadowAssassin->addParent($poisonBlade);
        $manager->persist($shadowAssassin);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ClassFixtures::class,
        ];
    }
}
