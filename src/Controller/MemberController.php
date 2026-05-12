<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEMBER')]
#[Route('/adherent')]
final class MemberController extends AbstractController
{
    #[Route('/vie-gem', name: 'app_member_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('member/dashboard.html.twig');
    }

    #[Route('/planning', name: 'app_member_planning')]
    public function planning(DocumentRepository $documentRepository): Response
    {
        return $this->render('member/planning.html.twig', [
            'planning' => $documentRepository->findLatestPlanning(),
        ]);
    }

    #[Route('/comptes-rendus', name: 'app_member_reports')]
    public function reports(DocumentRepository $documentRepository): Response
    {
        return $this->render('member/reports.html.twig', [
            'reports' => $documentRepository->findByType(Document::TYPE_REPORT),
        ]);
    }

    #[Route('/newsletter', name: 'app_member_newsletter')]
    public function newsletter(): Response
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $storageFile = $projectDir . '/var/data/newsletters.json';
        $newsletters = [];

        if (file_exists($storageFile)) {
            $data = json_decode((string) file_get_contents($storageFile), true);
            $newsletters = is_array($data) ? $data : [];
        }

        return $this->render('member/newsletter.html.twig', [
            'newsletters' => $newsletters,
        ]);
    }

}
