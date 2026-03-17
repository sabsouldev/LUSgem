<?php

namespace App\Controller;

use App\Entity\Proposition;
use App\Repository\PropositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/propositions')]
final class AdminPropositionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PropositionRepository $propositionRepository,
    ) {
    }

    #[Route('', name: 'app_admin_propositions', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/propositions/index.html.twig', [
            'propositions' => $this->propositionRepository->findAllOrderedByDate(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_propositions_show', methods: ['GET'])]
    public function show(Proposition $proposition): Response
    {
        // Marque automatiquement comme lu a la premiere ouverture.
        if (!$proposition->isEstLuParAdmin()) {
            $proposition->setEstLuParAdmin(true);
            $this->entityManager->flush();
        }

        return $this->render('admin/propositions/show.html.twig', [
            'proposition' => $proposition,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_propositions_delete', methods: ['POST'])]
    public function delete(Proposition $proposition, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_proposition_delete_' . $proposition->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_admin_propositions');
        }

        $this->entityManager->remove($proposition);
        $this->entityManager->flush();

        $this->addFlash('success', 'Proposition supprimée.');

        return $this->redirectToRoute('app_admin_propositions');
    }
}
