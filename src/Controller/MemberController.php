<?php

namespace App\Controller;

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
    public function planning(): Response
    {
        return $this->render('member/planning.html.twig');
    }

    #[Route('/comptes-rendus', name: 'app_member_reports')]
    public function reports(): Response
    {
        return $this->render('member/reports.html.twig');
    }

    #[Route('/newsletter', name: 'app_member_newsletter')]
    public function newsletter(): Response
    {
        return $this->render('member/newsletter.html.twig');
    }

}
