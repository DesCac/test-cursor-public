<?php

namespace App\Controller\Api;

use App\Entity\PlayerClass;
use App\Entity\Quest;
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
    #[Route('', name: 'api_skills_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $skills = $em->getRepository(Skill::class)->findAll();

        $payload = array_map(fn (Skill $skill) => [
            'id' => $skill->getId(),
            'name' => $skill->getName(),
            'tier' => $skill->getTier(),
            'requiredLevel' => $skill->getRequiredLevel(),
            'prerequisites' => array_map(
                static fn (Skill $parent) => [
                    'id' => $parent->getId(),
                    'name' => $parent->getName(),
                ],
                $skill->getPrerequisiteSkills()->toArray()
            ),
        ], $skills);

        return $this->json(['skills' => $payload]);
    }

    #[Route('/graph', name: 'api_skills_graph', methods: ['GET'])]
    public function graph(EntityManagerInterface $em): JsonResponse
    {
        return $this->json($this->buildGraphPayload($em));
    }

    #[Route('/graph', name: 'api_skills_update_graph', methods: ['PUT'])]
    public function updateGraph(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON in request body'], Response::HTTP_BAD_REQUEST);
        }

        $nodesPayload = $data['nodes'] ?? null;
        $connectionsPayload = $data['connections'] ?? null;

        if (!is_array($nodesPayload)) {
            return $this->json(['error' => 'Field "nodes" must be an array'], Response::HTTP_BAD_REQUEST);
        }

        if ($connectionsPayload !== null && !is_array($connectionsPayload)) {
            return $this->json(['error' => 'Field "connections" must be an array'], Response::HTTP_BAD_REQUEST);
        }

        $skillRepository = $em->getRepository(Skill::class);
        $existingSkills = [];
        foreach ($skillRepository->findAll() as $skillEntity) {
            if ($skillEntity->getId() !== null) {
                $existingSkills[$skillEntity->getId()] = $skillEntity;
            }
        }

        $classRepository = $em->getRepository(PlayerClass::class);
        $questRepository = $em->getRepository(Quest::class);

        $clientSkillMap = [];
        $processedSkills = [];

        foreach ($nodesPayload as $nodePayload) {
            if (!is_array($nodePayload)) {
                continue;
            }

            $skill = $this->hydrateSkillNode(
                $nodePayload,
                $existingSkills,
                $classRepository,
                $questRepository,
                $em
            );

            if (!$skill instanceof Skill) {
                continue;
            }

            $clientId = (string)($nodePayload['clientId'] ?? ($skill->getId() ?? uniqid('skill_', true)));
            $clientSkillMap[$clientId] = $skill;

            if ($skill->getId() !== null) {
                $clientSkillMap[(string)$skill->getId()] = $skill;
            }

            $processedSkills[] = $skill;
        }

        foreach ($existingSkills as $existing) {
            if (!in_array($existing, $processedSkills, true)) {
                $em->remove($existing);
            }
        }

        $em->flush();

        // refresh mapping with generated ids
        foreach ($clientSkillMap as $key => $skill) {
            if ($skill->getId() !== null) {
                $clientSkillMap[(string)$skill->getId()] = $skill;
            }
        }

        // Clear prerequisites first
        foreach ($processedSkills as $skill) {
            $skill->clearPrerequisiteSkills();
        }

        if (is_array($connectionsPayload)) {
            foreach ($connectionsPayload as $connectionPayload) {
                if (!is_array($connectionPayload)) {
                    continue;
                }

                $sourceKey = (string)($connectionPayload['sourceId'] ?? '');
                $targetKey = (string)($connectionPayload['targetId'] ?? '');

                if ($sourceKey === '' || $targetKey === '') {
                    continue;
                }

                if (!isset($clientSkillMap[$sourceKey], $clientSkillMap[$targetKey])) {
                    continue;
                }

                /** @var Skill $source */
                $source = $clientSkillMap[$sourceKey];
                /** @var Skill $target */
                $target = $clientSkillMap[$targetKey];

                if ($source === $target) {
                    continue;
                }

                $target->addPrerequisiteSkill($source);
            }
        }

        $em->flush();

        return $this->json($this->buildGraphPayload($em));
    }

    #[Route('/{id}', name: 'api_skills_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getSkill(int $id, EntityManagerInterface $em): JsonResponse
    {
        $skill = $em->getRepository(Skill::class)->find($id);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeSkill($skill));
    }

    #[Route('/{id}', name: 'api_skills_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function updateSkill(int $id, Request $request, EntityManagerInterface $em): JsonResponse
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
            $skill->setName((string)$data['name']);
        }

        if (isset($data['tier'])) {
            $skill->setTier((string)$data['tier']);
        }

        if (array_key_exists('description', $data)) {
            $skill->setDescription($data['description'] !== null ? (string)$data['description'] : null);
        }

        if (array_key_exists('requiredLevel', $data)) {
            $skill->setRequiredLevel($data['requiredLevel'] !== null ? (int)$data['requiredLevel'] : null);
        }

        if (array_key_exists('metadata', $data)) {
            $skill->setMetadata($this->normalizeArray($data['metadata']));
        }

        if (array_key_exists('extraRequirements', $data)) {
            $skill->setExtraRequirements($this->normalizeArray($data['extraRequirements']));
        }

        $em->flush();

        return $this->json($this->serializeSkill($skill));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGraphPayload(EntityManagerInterface $em): array
    {
        $skills = $em->getRepository(Skill::class)->findAll();
        $classes = $em->getRepository(PlayerClass::class)->findAll();
        $quests = $em->getRepository(Quest::class)->findAll();

        return [
            'skills' => array_map(fn (Skill $skill) => $this->serializeSkill($skill), $skills),
            'classes' => array_map(static fn (PlayerClass $class) => [
                'id' => $class->getId(),
                'name' => $class->getName(),
                'parentId' => $class->getParent()?->getId(),
                'description' => $class->getDescription(),
            ], $classes),
            'quests' => array_map(static fn (Quest $quest) => [
                'id' => $quest->getId(),
                'name' => $quest->getName(),
            ], $quests),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, Skill> $existingSkills
     */
    private function hydrateSkillNode(
        array $payload,
        array &$existingSkills,
        $classRepository,
        $questRepository,
        EntityManagerInterface $em
    ): ?Skill {
        $skillId = isset($payload['id']) && is_numeric($payload['id']) ? (int) $payload['id'] : null;

        if ($skillId !== null && $skillId > 0 && isset($existingSkills[$skillId])) {
            $skill = $existingSkills[$skillId];
        } else {
            $skill = new Skill();
            $em->persist($skill);
            if ($skillId !== null && $skillId > 0) {
                $existingSkills[$skillId] = $skill;
            }
        }

        $skill->setName((string)($payload['name'] ?? $skill->getName() ?? 'Новый скилл'));
        $skill->setTier((string)($payload['tier'] ?? $skill->getTier() ?? 'skill'));
        $skill->setDescription(isset($payload['description']) ? (string)$payload['description'] : null);
        $skill->setMetadata($this->normalizeArray($payload['metadata'] ?? null));
        $skill->setExtraRequirements($this->normalizeArray($payload['extraRequirements'] ?? null));
        $skill->setRequiredLevel(isset($payload['requiredLevel']) ? (int)$payload['requiredLevel'] : null);
        $skill->setPositionX(isset($payload['positionX']) ? (float)$payload['positionX'] : null);
        $skill->setPositionY(isset($payload['positionY']) ? (float)$payload['positionY'] : null);

        // Update required classes
        $requiredClassIds = $this->normalizeIdList($payload['requiredClassIds'] ?? []);
        foreach ($skill->getRequiredClasses()->toArray() as $existingClass) {
            $skill->removeRequiredClass($existingClass);
        }

        if ($requiredClassIds !== []) {
            $classes = $classRepository->createQueryBuilder('c')
                ->where('c.id IN (:ids)')
                ->setParameter('ids', $requiredClassIds)
                ->getQuery()
                ->getResult();

            foreach ($classes as $class) {
                if ($class instanceof PlayerClass) {
                    $skill->addRequiredClass($class);
                }
            }
        }

        // Update required quests
        $requiredQuestIds = $this->normalizeIdList($payload['requiredQuestIds'] ?? []);
        foreach ($skill->getRequiredQuests()->toArray() as $existingQuest) {
            $skill->removeRequiredQuest($existingQuest);
        }

        if ($requiredQuestIds !== []) {
            $quests = $questRepository->createQueryBuilder('q')
                ->where('q.id IN (:ids)')
                ->setParameter('ids', $requiredQuestIds)
                ->getQuery()
                ->getResult();

            foreach ($quests as $quest) {
                if ($quest instanceof Quest) {
                    $skill->addRequiredQuest($quest);
                }
            }
        }

        return $skill;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSkill(Skill $skill): array
    {
        return [
            'id' => $skill->getId(),
            'name' => $skill->getName(),
            'tier' => $skill->getTier(),
            'description' => $skill->getDescription(),
            'metadata' => $skill->getMetadata(),
            'extraRequirements' => $skill->getExtraRequirements(),
            'requiredLevel' => $skill->getRequiredLevel(),
            'positionX' => $skill->getPositionX(),
            'positionY' => $skill->getPositionY(),
            'requiredClassIds' => array_map(
                static fn (PlayerClass $class) => $class->getId(),
                $skill->getRequiredClasses()->toArray()
            ),
            'requiredQuestIds' => array_map(
                static fn (Quest $quest) => $quest->getId(),
                $skill->getRequiredQuests()->toArray()
            ),
            'prerequisiteIds' => array_map(
                static fn (Skill $parent) => $parent->getId(),
                $skill->getPrerequisiteSkills()->toArray()
            ),
            'createdAt' => $skill->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $skill->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @param mixed $value
     * @return array<mixed>|null
     */
    private function normalizeArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value === [] ? null : $value;
        }

        return null;
    }

    /**
     * @param mixed $value
     * @return list<int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            $ids[] = (int)$item;
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id) => $id > 0)));
    }
}

