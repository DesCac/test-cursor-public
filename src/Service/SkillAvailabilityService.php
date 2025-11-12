<?php

namespace App\Service;

use App\Entity\CharacterSkill;
use App\Entity\PlayerCharacter;
use App\Entity\PlayerClass;
use App\Entity\Quest;
use App\Entity\Skill;
use Doctrine\Common\Collections\Collection;
use JsonException;

class SkillAvailabilityService
{
    /**
     * Checks whether the character already unlocked the skill.
     */
    public function isSkillUnlocked(Skill $skill, PlayerCharacter $character): bool
    {
        foreach ($character->getCharacterSkills() as $characterSkill) {
            if ($characterSkill->getSkill()?->getId() === $skill->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{canUnlock: bool, alreadyUnlocked: bool, reasons: list<string>}
     */
    public function evaluateUnlock(Skill $skill, PlayerCharacter $character): array
    {
        $reasons = [];

        if ($this->isSkillUnlocked($skill, $character)) {
            return [
                'canUnlock' => false,
                'alreadyUnlocked' => true,
                'reasons' => ['Скилл уже открыт персонажем'],
            ];
        }

        $this->assertLevelRequirement($skill, $character, $reasons);
        $this->assertClassRequirement($skill, $character, $reasons);
        $this->assertQuestRequirement($skill, $character, $reasons);
        $this->assertPrerequisites($skill, $character, $reasons);
        $this->assertExtraRequirements($skill, $reasons);

        return [
            'canUnlock' => $reasons === [],
            'alreadyUnlocked' => false,
            'reasons' => $reasons,
        ];
    }

    public function canUnlock(Skill $skill, PlayerCharacter $character): bool
    {
        return $this->evaluateUnlock($skill, $character)['canUnlock'];
    }

    /**
     * @param list<string> $reasons
     */
    private function assertLevelRequirement(Skill $skill, PlayerCharacter $character, array &$reasons): void
    {
        $requiredLevel = $skill->getRequiredLevel();
        if ($requiredLevel !== null && $character->getLevel() < $requiredLevel) {
            $reasons[] = sprintf('Требуется уровень персонажа %d', $requiredLevel);
        }
    }

    /**
     * @param list<string> $reasons
     */
    private function assertClassRequirement(Skill $skill, PlayerCharacter $character, array &$reasons): void
    {
        $requiredClasses = $skill->getRequiredClasses();
        if ($requiredClasses->isEmpty()) {
            return;
        }

        $characterClass = $character->getClass();
        if ($characterClass === null) {
            $reasons[] = 'У персонажа не задан класс';
            return;
        }

        $characterLineageIds = $this->collectClassLineageIds($characterClass);
        $requiredClassNames = [];

        $matched = false;
        foreach ($requiredClasses as $requiredClass) {
            $requiredClassNames[] = $requiredClass->getName();
            if ($requiredClass->getId() !== null && in_array($requiredClass->getId(), $characterLineageIds, true)) {
                $matched = true;
            }
        }

        if (!$matched) {
            $reasons[] = sprintf(
                'Требуется класс из набора: %s',
                implode(', ', $requiredClassNames)
            );
        }
    }

    /**
     * @param list<string> $reasons
     */
    private function assertQuestRequirement(Skill $skill, PlayerCharacter $character, array &$reasons): void
    {
        $requiredQuests = $skill->getRequiredQuests();
        if ($requiredQuests->isEmpty()) {
            return;
        }

        $completedIds = $this->collectQuestIds($character->getCompletedQuests());
        $missing = [];

        /** @var Quest $quest */
        foreach ($requiredQuests as $quest) {
            $questId = $quest->getId();
            if ($questId === null || !in_array($questId, $completedIds, true)) {
                $missing[] = $quest->getName() ?? sprintf('Quest #%d', $questId ?? 0);
            }
        }

        if ($missing !== []) {
            $reasons[] = sprintf(
                'Необходимо завершить квесты: %s',
                implode(', ', $missing)
            );
        }
    }

    /**
     * @param list<string> $reasons
     */
    private function assertPrerequisites(Skill $skill, PlayerCharacter $character, array &$reasons): void
    {
        if ($skill->getPrerequisiteSkills()->isEmpty()) {
            return;
        }

        $unlockedSkillIds = $this->collectUnlockedSkillIds($character->getCharacterSkills());
        $missing = [];

        foreach ($skill->getPrerequisiteSkills() as $parentSkill) {
            $parentId = $parentSkill->getId();
            if ($parentId === null || !in_array($parentId, $unlockedSkillIds, true)) {
                $missing[] = $parentSkill->getName();
            }
        }

        if ($missing !== []) {
            $reasons[] = sprintf(
                'Требуется открыть все родительские скиллы: %s',
                implode(', ', $missing)
            );
        }
    }

    /**
     * @param list<string> $reasons
     */
    private function assertExtraRequirements(Skill $skill, array &$reasons): void
    {
        $extra = $skill->getExtraRequirements();
        if ($extra === null || $extra === []) {
            return;
        }

        // Support declarative schema:
        // {
        //   "attributes": {"agility": 5},
        //   "flags": {"hasReputation": true}
        // }
        // Since the domain model does not yet expose these attributes,
        // we record the requirement as informational message.
        try {
            $encoded = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $encoded = '[некорректный JSON условий]';
        }

        $reasons[] = sprintf(
            'Дополнительные условия требуют проверки на стороне геймплея: %s',
            $encoded
        );
    }

    /**
     * @return list<int>
     */
    private function collectClassLineageIds(PlayerClass $class): array
    {
        $ids = [];
        $current = $class;

        while ($current !== null) {
            if ($current->getId() !== null) {
                $ids[] = $current->getId();
            }
            $current = $current->getParent();
        }

        return $ids;
    }

    /**
     * @param Collection<int, Quest> $quests
     * @return list<int>
     */
    private function collectQuestIds(Collection $quests): array
    {
        $ids = [];
        foreach ($quests as $quest) {
            if ($quest->getId() !== null) {
                $ids[] = $quest->getId();
            }
        }

        return $ids;
    }

    /**
     * @param Collection<int, CharacterSkill> $characterSkills
     * @return list<int>
     */
    private function collectUnlockedSkillIds(Collection $characterSkills): array
    {
        $ids = [];
        foreach ($characterSkills as $characterSkill) {
            $skillId = $characterSkill->getSkill()?->getId();
            if ($skillId !== null) {
                $ids[] = $skillId;
            }
        }

        return $ids;
    }
}

