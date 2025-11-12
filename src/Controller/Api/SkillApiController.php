<?php

namespace App\Controller\Api;

use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/skills')]
class SkillApiController extends AbstractController
{
    #[Route('/{id}', name: 'api_skill_get', methods: ['GET'])]
    public function get(int $id, EntityManagerInterface $em): JsonResponse
    {
        $skill = $em->getRepository(Skill::class)->find($id);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeSkill($skill));
    }

    #[Route('/{id}', name: 'api_skill_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $skill = $em->getRepository(Skill::class)->find($id);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON in request body'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $skill->setName($data['name']);
        }

        if (isset($data['description'])) {
            $skill->setDescription($data['description']);
        }

        if (isset($data['unlockConditions'])) {
            $skill->setUnlockConditions($this->normalizeArray($data['unlockConditions']));
        }

        if (isset($data['effects'])) {
            $skill->setEffects($this->normalizeArray($data['effects']));
        }

        $em->flush();

        return $this->json($this->serializeSkill($skill));
    }

    #[Route('/{id}/tree', name: 'api_skill_update_tree', methods: ['PUT'])]
    public function updateTree(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $skill = $em->getRepository(Skill::class)->find($id);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON in request body'], Response::HTTP_BAD_REQUEST);
        }

        $skillsPayload = $data['skills'] ?? [];
        $dependenciesPayload = $data['dependencies'] ?? [];

        if (!is_array($skillsPayload)) {
            return $this->json(['error' => 'Field "skills" must be an array'], Response::HTTP_BAD_REQUEST);
        }

        $existingSkills = $em->getRepository(Skill::class)->findAll();
        $skillMap = [];
        foreach ($existingSkills as $s) {
            if ($s->getId() !== null) {
                $skillMap[$s->getId()] = $s;
            }
        }

        foreach ($skillsPayload as $skillPayload) {
            if (!is_array($skillPayload)) {
                continue;
            }

            $skillId = isset($skillPayload['id']) ? (int) $skillPayload['id'] : null;

            if ($skillId !== null && isset($skillMap[$skillId])) {
                $skillEntity = $skillMap[$skillId];
            } else {
                $skillEntity = new Skill();
                $em->persist($skillEntity);
            }

            if (isset($skillPayload['name'])) {
                $skillEntity->setName($skillPayload['name']);
            }
            if (isset($skillPayload['description'])) {
                $skillEntity->setDescription($skillPayload['description']);
            }
            if (isset($skillPayload['unlockConditions'])) {
                $skillEntity->setUnlockConditions($this->normalizeArray($skillPayload['unlockConditions']));
            }
            if (isset($skillPayload['effects'])) {
                $skillEntity->setEffects($this->normalizeArray($skillPayload['effects']));
            }
            if (isset($skillPayload['positionX'])) {
                $skillEntity->setPositionX((float) $skillPayload['positionX']);
            }
            if (isset($skillPayload['positionY'])) {
                $skillEntity->setPositionY((float) $skillPayload['positionY']);
            }

            if ($skillId === null) {
                $em->flush();
                if ($skillEntity->getId() !== null) {
                    $skillMap[$skillEntity->getId()] = $skillEntity;
                }
            }
        }

        // Clear all dependencies first
        foreach ($skillMap as $s) {
            foreach ($s->getParents()->toArray() as $parent) {
                $s->removeParent($parent);
            }
        }

        $em->flush();

        // Add new dependencies
        if (is_array($dependenciesPayload)) {
            foreach ($dependenciesPayload as $depPayload) {
                if (!is_array($depPayload)) {
                    continue;
                }

                $childId = isset($depPayload['childId']) ? (int) $depPayload['childId'] : null;
                $parentId = isset($depPayload['parentId']) ? (int) $depPayload['parentId'] : null;

                if ($childId === null || $parentId === null) {
                    continue;
                }

                if (!isset($skillMap[$childId], $skillMap[$parentId])) {
                    continue;
                }

                $childSkill = $skillMap[$childId];
                $parentSkill = $skillMap[$parentId];

                $childSkill->addParent($parentSkill);
            }
        }

        $em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSkill(Skill $skill): array
    {
        $parents = [];
        foreach ($skill->getParents() as $parent) {
            $parents[] = [
                'id' => $parent->getId(),
                'name' => $parent->getName(),
            ];
        }

        $children = [];
        foreach ($skill->getChildren() as $child) {
            $children[] = [
                'id' => $child->getId(),
                'name' => $child->getName(),
            ];
        }

        return [
            'id' => $skill->getId(),
            'name' => $skill->getName(),
            'description' => $skill->getDescription(),
            'unlockConditions' => $skill->getUnlockConditions(),
            'effects' => $skill->getEffects(),
            'positionX' => $skill->getPositionX(),
            'positionY' => $skill->getPositionY(),
            'parents' => $parents,
            'children' => $children,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|null
     */
    private function normalizeArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return null;
        }

        if ($value === []) {
            return null;
        }

        return $value;
    }
}
