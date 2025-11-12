<?php

namespace App\Service;

use App\Entity\PlayerCharacter;
use App\Entity\PlayerClass;
use App\Entity\Skill;
use App\Entity\SkillLink;

class SkillAvailabilityService
{
    /**
     * Проверяем, соответствует ли персонаж всем условиям открытия навыка.
     */
    public function canUnlockSkill(PlayerCharacter $character, Skill $skill): bool
    {
        return $this->getBlockingReasons($character, $skill) === [];
    }

    /**
     * Возвращает список причин, почему навык недоступен (пусто = доступен).
     *
     * @return list<string>
     */
    public function getBlockingReasons(PlayerCharacter $character, Skill $skill): array
    {
        $reasons = [];

        if ($this->characterHasSkill($character, $skill)) {
            $reasons[] = 'Навык уже открыт';
            return $reasons;
        }

        $requiredLevel = $skill->getRequiredLevel();
        if ($requiredLevel !== null && $character->getLevel() < $requiredLevel) {
            $reasons[] = sprintf('Требуется уровень %d', $requiredLevel);
        }

        $requiredClasses = $skill->getRequiredClasses();
        if ($requiredClasses->count() > 0 && !$this->characterMatchesClasses($character, $requiredClasses->toArray())) {
            $names = array_map(static fn(PlayerClass $class) => $class->getName(), $requiredClasses->toArray());
            $reasons[] = sprintf('Необходим класс: %s', implode(', ', $names));
        }

        if (!$this->hasRequiredQuests($character, $skill)) {
            $questNames = array_map(
                static fn($quest) => $quest->getName(),
                $skill->getRequiredQuests()->toArray()
            );
            $reasons[] = sprintf('Нужно завершить квесты: %s', implode(', ', $questNames));
        }

        $linkReasons = $this->checkParentLinks($character, $skill);
        array_push($reasons, ...$linkReasons);

        $ruleReasons = $this->checkAvailabilityRules($character, $skill);
        array_push($reasons, ...$ruleReasons);

        return $reasons;
    }

    private function characterHasSkill(PlayerCharacter $character, Skill $skill): bool
    {
        foreach ($character->getSkills() as $characterSkill) {
            if ($characterSkill->getSkill()?->getId() === $skill->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, PlayerClass> $requiredClasses
     */
    private function characterMatchesClasses(PlayerCharacter $character, array $requiredClasses): bool
    {
        $availableClasses = $this->flattenClassHierarchy($character->getPlayerClass());

        foreach ($requiredClasses as $required) {
            if (!in_array($required->getId(), $availableClasses, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Возвращает список идентификаторов классов (текущий + все предки).
     *
     * @return list<int>
     */
    private function flattenClassHierarchy(?PlayerClass $class): array
    {
        $result = [];
        $current = $class;

        while ($current !== null) {
            $id = $current->getId();
            if ($id !== null) {
                $result[] = $id;
            }
            $current = $current->getParent();
        }

        return $result;
    }

    private function hasRequiredQuests(PlayerCharacter $character, Skill $skill): bool
    {
        if ($skill->getRequiredQuests()->count() === 0) {
            return true;
        }

        $completedIds = [];
        foreach ($character->getCompletedQuests() as $quest) {
            $id = $quest->getId();
            if ($id !== null) {
                $completedIds[] = $id;
            }
        }

        foreach ($skill->getRequiredQuests() as $requiredQuest) {
            $id = $requiredQuest->getId();
            if ($id !== null && !in_array($id, $completedIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function checkParentLinks(PlayerCharacter $character, Skill $skill): array
    {
        if ($skill->getIncomingLinks()->count() === 0) {
            return [];
        }

        $reasons = [];
        /** @var SkillLink $link */
        foreach ($skill->getIncomingLinks() as $link) {
            $parent = $link->getParentSkill();
            if ($parent === null) {
                continue;
            }

            $parentUnlocked = $this->characterHasSkill($character, $parent);

            if ($link->requiresAllParents() && !$parentUnlocked) {
                $reasons[] = sprintf('Нужно освоить навык "%s"', $parent->getName());
            }
        }

        // Если есть хотя бы один линк с requireAll=false, допускаем доступ при одном открытом родителе
        $optionalLinks = array_filter(
            $skill->getIncomingLinks()->toArray(),
            static fn(SkillLink $link) => !$link->requiresAllParents()
        );

        if ($optionalLinks !== []) {
            $hasOptionalParent = false;
            /** @var SkillLink $optional */
            foreach ($optionalLinks as $optional) {
                $parent = $optional->getParentSkill();
                if ($parent !== null && $this->characterHasSkill($character, $parent)) {
                    $hasOptionalParent = true;
                    break;
                }
            }

            if (!$hasOptionalParent) {
                $names = array_map(
                    static fn(SkillLink $link) => $link->getParentSkill()?->getName(),
                    $optionalLinks
                );
                $names = array_filter($names);
                if ($names !== []) {
                    $reasons[] = sprintf('Нужно хотя бы одно из навыков: %s', implode(', ', $names));
                }
            }
        }

        return $reasons;
    }

    /**
     * @return list<string>
     */
    private function checkAvailabilityRules(PlayerCharacter $character, Skill $skill): array
    {
        $rules = $skill->getAvailabilityRules();
        if ($rules === null || $rules === []) {
            return [];
        }

        $reasons = [];
        $attributes = $character->getAttributes() ?? [];

        if (isset($rules['attributes']) && is_array($rules['attributes'])) {
            foreach ($rules['attributes'] as $attribute => $constraint) {
                if (!is_array($constraint)) {
                    continue;
                }

                $value = $attributes[$attribute] ?? null;

                if (array_key_exists('min', $constraint) && (!is_numeric($value) || $value < $constraint['min'])) {
                    $reasons[] = sprintf('Параметр "%s" должен быть не ниже %s', $attribute, $constraint['min']);
                    continue;
                }

                if (array_key_exists('max', $constraint) && (!is_numeric($value) || $value > $constraint['max'])) {
                    $reasons[] = sprintf('Параметр "%s" должен быть не выше %s', $attribute, $constraint['max']);
                    continue;
                }

                if (array_key_exists('equals', $constraint) && $value !== $constraint['equals']) {
                    $reasons[] = sprintf('Параметр "%s" должен равняться %s', $attribute, (string) $constraint['equals']);
                }
            }
        }

        if (isset($rules['customMessage']) && $reasons !== []) {
            $reasons = [ (string) $rules['customMessage'] ];
        }

        return $reasons;
    }
}

