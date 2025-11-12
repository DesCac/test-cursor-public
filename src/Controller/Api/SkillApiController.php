<?php

namespace App\Controller\Api;

use App\Entity\PlayerClass;
use App\Entity\Quest;
use App\Entity\Skill;
use App\Entity\SkillLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/skills')]
class SkillApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger
    ) {
    }

    #[Route('', name: 'api_skill_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $skills = $this->entityManager->getRepository(Skill::class)->findBy([], ['name' => 'ASC']);

        return $this->json([
            'skills' => array_map(
                fn(Skill $skill) => $this->serializeSkill($skill),
                $skills
            ),
        ]);
    }

    #[Route('/{id}/graph', name: 'api_skill_graph', methods: ['GET'])]
    public function graph(int $id): JsonResponse
    {
        $focus = $this->entityManager->getRepository(Skill::class)->find($id);
        if (!$focus) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeGraph($focus));
    }

    #[Route('/graph', name: 'api_skill_update_graph', methods: ['PUT'])]
    public function updateGraph(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON in request body'], Response::HTTP_BAD_REQUEST);
        }

        $nodesPayload = $payload['nodes'] ?? [];
        $linksPayload = $payload['links'] ?? [];
        $deletedIds = $payload['deletedSkillIds'] ?? [];
        $focusId = isset($payload['focusSkillId']) ? (int) $payload['focusSkillId'] : null;

        if (!is_array($nodesPayload) || !is_array($linksPayload)) {
            return $this->json(['error' => 'Fields "nodes" and "links" must be arrays'], Response::HTTP_BAD_REQUEST);
        }

        $skillsRepo = $this->entityManager->getRepository(Skill::class);
        $classRepo = $this->entityManager->getRepository(PlayerClass::class);
        $questRepo = $this->entityManager->getRepository(Quest::class);

        /** @var array<int, Skill> $existingSkills */
        $existingSkills = [];
        foreach ($skillsRepo->findAll() as $skill) {
            if ($skill->getId() !== null) {
                $existingSkills[$skill->getId()] = $skill;
            }
        }

        // Handle deletions explicitly requested by client
        if (is_array($deletedIds) && $deletedIds !== []) {
            foreach ($deletedIds as $rawId) {
                $skillId = (int) $rawId;
                if ($skillId <= 0) {
                    continue;
                }
                if (!isset($existingSkills[$skillId])) {
                    continue;
                }

                $skill = $existingSkills[$skillId];
                if ($skill->getCharacterSkills()->count() > 0) {
                    return $this->json([
                        'error' => sprintf('Skill "%s" is used by characters and cannot be deleted', $skill->getName()),
                    ], Response::HTTP_BAD_REQUEST);
                }

                $this->entityManager->remove($skill);
                unset($existingSkills[$skillId]);
            }
        }

        $clientSkillMap = [];

        foreach ($nodesPayload as $nodePayload) {
            if (!is_array($nodePayload)) {
                continue;
            }

            $skillId = isset($nodePayload['id']) ? (int) $nodePayload['id'] : null;
            $clientId = (string) ($nodePayload['clientId'] ?? ($skillId ?? uniqid('skill_', true)));

            if ($skillId !== null && isset($existingSkills[$skillId])) {
                $skill = $existingSkills[$skillId];
            } else {
                $name = (string) ($nodePayload['name'] ?? 'Новый навык');
                $slug = (string) ($nodePayload['slug'] ?? $this->slugify($name));
                $skill = new Skill($name, $slug);
                $this->entityManager->persist($skill);
            }

            if (isset($nodePayload['name'])) {
                $skill->setName((string) $nodePayload['name']);
            }

            $slugCandidate = $nodePayload['slug'] ?? $skill->getSlug();
            if (!is_string($slugCandidate) || trim($slugCandidate) === '') {
                $slugCandidate = $this->slugify($skill->getName());
            }
            $skill->setSlug($this->ensureUniqueSlug($skill, $slugCandidate));

            $skill->setDescription($nodePayload['description'] ?? null);
            $skill->setRequiredLevel(
                array_key_exists('requiredLevel', $nodePayload) && $nodePayload['requiredLevel'] !== null
                    ? (int) $nodePayload['requiredLevel']
                    : null
            );
            $skill->setAvailabilityRules($this->normalizeArray($nodePayload['availabilityRules'] ?? null));
            $skill->setPositionX(isset($nodePayload['positionX']) ? (float) $nodePayload['positionX'] : null);
            $skill->setPositionY(isset($nodePayload['positionY']) ? (float) $nodePayload['positionY'] : null);

            // Rebuild required classes
            foreach ($skill->getRequiredClasses()->toArray() as $existingClass) {
                $skill->removeRequiredClass($existingClass);
            }
            $requiredClasses = $nodePayload['requiredClasses'] ?? [];
            if (is_array($requiredClasses)) {
                $classIds = array_values(array_unique(array_map('intval', $requiredClasses)));
                if ($classIds !== []) {
                    $classes = $classRepo->createQueryBuilder('c')
                        ->where('c.id IN (:ids)')
                        ->setParameter('ids', $classIds)
                        ->getQuery()
                        ->getResult();
                    /** @var PlayerClass $class */
                    foreach ($classes as $class) {
                        $skill->addRequiredClass($class);
                    }
                }
            }

            // Rebuild required quests
            foreach ($skill->getRequiredQuests()->toArray() as $existingQuest) {
                $skill->removeRequiredQuest($existingQuest);
            }
            $requiredQuests = $nodePayload['requiredQuests'] ?? [];
            if (is_array($requiredQuests)) {
                $questIds = array_values(array_unique(array_map('intval', $requiredQuests)));
                if ($questIds !== []) {
                    $quests = $questRepo->createQueryBuilder('q')
                        ->where('q.id IN (:ids)')
                        ->setParameter('ids', $questIds)
                        ->getQuery()
                        ->getResult();
                    /** @var Quest $quest */
                    foreach ($quests as $quest) {
                        $skill->addRequiredQuest($quest);
                    }
                }
            }

            $clientSkillMap[$clientId] = $skill;
            if ($skill->getId() !== null) {
                $clientSkillMap[(string) $skill->getId()] = $skill;
            }
        }

        $this->entityManager->flush();

        // Refresh mapping with generated IDs
        foreach ($clientSkillMap as $key => $skill) {
            if ($skill->getId() !== null) {
                $clientSkillMap[(string) $skill->getId()] = $skill;
            }
        }

        // Remove previous links and rebuild from payload
        $linkRepo = $this->entityManager->getRepository(SkillLink::class);
        foreach ($linkRepo->findAll() as $link) {
            $this->entityManager->remove($link);
        }
        $this->entityManager->flush();

        foreach ($linksPayload as $linkPayload) {
            if (!is_array($linkPayload)) {
                continue;
            }

            $parentKey = (string) ($linkPayload['parentId'] ?? '');
            $childKey = (string) ($linkPayload['childId'] ?? '');

            if ($parentKey === '' || $childKey === '') {
                continue;
            }

            if (!isset($clientSkillMap[$parentKey], $clientSkillMap[$childKey])) {
                continue;
            }

            /** @var Skill $parent */
            $parent = $clientSkillMap[$parentKey];
            /** @var Skill $child */
            $child = $clientSkillMap[$childKey];

            if ($parent->getId() === $child->getId()) {
                continue; // avoid self loops
            }

            $link = new SkillLink();
            $link->setParentSkill($parent);
            $link->setChildSkill($child);
            $link->setRequiresAllParents(!array_key_exists('requiresAllParents', $linkPayload) || (bool) $linkPayload['requiresAllParents']);
            $link->setMetadata($this->normalizeArray($linkPayload['metadata'] ?? null));

            $this->entityManager->persist($link);
        }

        $this->entityManager->flush();

        $focusSkill = null;
        if ($focusId !== null) {
            $focusSkill = $skillsRepo->find($focusId);
        }
        if ($focusSkill === null) {
            $focusSkill = $skillsRepo->findOneBy([], ['id' => 'ASC']);
        }

        if ($focusSkill === null) {
            return $this->json(['error' => 'No skills available after update'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializeGraph($focusSkill));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeGraph(Skill $focus): array
    {
        $skills = $this->entityManager->getRepository(Skill::class)->findBy([], ['name' => 'ASC']);
        $links = $this->entityManager->getRepository(SkillLink::class)->findAll();

        return [
            'focusSkillId' => $focus->getId(),
            'nodes' => array_map(fn(Skill $skill) => $this->serializeSkill($skill), $skills),
            'links' => array_map(fn(SkillLink $link) => $this->serializeLink($link), $links),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSkill(Skill $skill): array
    {
        return [
            'id' => $skill->getId(),
            'name' => $skill->getName(),
            'slug' => $skill->getSlug(),
            'description' => $skill->getDescription(),
            'requiredLevel' => $skill->getRequiredLevel(),
            'availabilityRules' => $skill->getAvailabilityRules(),
            'positionX' => $skill->getPositionX(),
            'positionY' => $skill->getPositionY(),
            'requiredClasses' => array_values(array_filter(array_map(
                static fn(PlayerClass $class) => $class->getId(),
                $skill->getRequiredClasses()->toArray()
            ))),
            'requiredQuests' => array_values(array_filter(array_map(
                static fn(Quest $quest) => $quest->getId(),
                $skill->getRequiredQuests()->toArray()
            ))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLink(SkillLink $link): array
    {
        return [
            'id' => $link->getId(),
            'parentId' => $link->getParentSkill()?->getId(),
            'childId' => $link->getChildSkill()?->getId(),
            'requiresAllParents' => $link->requiresAllParents(),
            'metadata' => $link->getMetadata(),
        ];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|null
     */
    private function normalizeArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            try {
                $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
            return is_array($decoded) && $decoded !== [] ? $decoded : null;
        }

        if (is_array($value)) {
            return $value === [] ? null : $value;
        }

        return null;
    }

    private function slugify(string $value): string
    {
        $slug = (string) $this->slugger->slug($value)->lower();
        return $slug !== '' ? $slug : 'skill-' . uniqid();
    }

    private function ensureUniqueSlug(Skill $skill, string $candidate): string
    {
        $baseSlug = $this->slugify($candidate);
        $uniqueSlug = $baseSlug;
        $counter = 1;

        $repository = $this->entityManager->getRepository(Skill::class);

        while (true) {
            $existing = $repository->findOneBy(['slug' => $uniqueSlug]);
            if ($existing === null || $existing->getId() === $skill->getId()) {
                break;
            }

            $uniqueSlug = $baseSlug . '-' . $counter++;
        }

        return $uniqueSlug;
    }
}

