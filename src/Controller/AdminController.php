<?php

namespace App\Controller;

use App\Entity\NPC;
use App\Entity\Quest;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/npcs', name: 'admin_npcs_list')]
    public function npcsList(EntityManagerInterface $em): Response
    {
        $npcs = $em->getRepository(NPC::class)->findAll();

        return $this->render('admin/npcs/list.html.twig', [
            'npcs' => $npcs,
        ]);
    }

    #[Route('/npcs/{id}/edit', name: 'admin_npcs_edit', requirements: ['id' => '\d+'])]
    public function npcsEdit(int $id, EntityManagerInterface $em): Response
    {
        $npc = $em->getRepository(NPC::class)->find($id);

        if (!$npc) {
            throw $this->createNotFoundException('NPC not found');
        }

        return $this->render('admin/npcs/edit.html.twig', [
            'npc' => $npc,
        ]);
    }

    #[Route('/quests', name: 'admin_quests_list')]
    public function questsList(EntityManagerInterface $em): Response
    {
        $quests = $em->getRepository(Quest::class)->findAll();

        return $this->render('admin/quests/list.html.twig', [
            'quests' => $quests,
        ]);
    }

    #[Route('/quests/{id}/edit', name: 'admin_quests_edit', requirements: ['id' => '\d+'])]
    public function questsEdit(int $id, EntityManagerInterface $em): Response
    {
        $quest = $em->getRepository(Quest::class)->find($id);

        if (!$quest) {
            throw $this->createNotFoundException('Quest not found');
        }

        return $this->render('admin/quests/edit.html.twig', [
            'quest' => $quest,
        ]);
    }

    #[Route('/skills', name: 'admin_skills_list')]
    public function skillsList(EntityManagerInterface $em): Response
    {
        $skills = $em->getRepository(Skill::class)->findAll();

        return $this->render('admin/skills/list.html.twig', [
            'skills' => $skills,
        ]);
    }

    #[Route('/skills/{id}/edit', name: 'admin_skills_edit', requirements: ['id' => '\d+'])]
    public function skillsEdit(int $id, EntityManagerInterface $em): Response
    {
        $skill = $em->getRepository(Skill::class)->find($id);

        if (!$skill) {
            throw $this->createNotFoundException('Skill not found');
        }

        return $this->render('admin/skills/edit.html.twig', [
            'skill' => $skill,
        ]);
    }
}
