<?php

namespace App\Controller;

use App\Entity\Proposition;
use App\Entity\User;
use App\Repository\PropositionRepository;
use App\Security\PropositionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEMBER')]
#[Route('/adherent/propositions')]
final class AdherentPropositionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PropositionRepository $propositionRepository,
    ) {
    }

    #[Route('', name: 'app_member_propositions', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('member_proposition', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');

                return $this->redirectToRoute('app_member_propositions');
            }

            $titre = trim((string) $request->request->get('titre', ''));
            $contenu = trim((string) $request->request->get('contenu', ''));

            if ($titre === '' || $contenu === '') {
                $this->addFlash('error', 'Le titre et le contenu sont obligatoires.');

                return $this->redirectToRoute('app_member_propositions');
            }

            $proposition = new Proposition();
            $proposition->setAuteur($user);
            $proposition->setTitre(mb_substr($titre, 0, 200));
            $proposition->setContenu(mb_substr($contenu, 0, 4000));

            $this->entityManager->persist($proposition);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre proposition a été transmise.');

            return $this->redirectToRoute('app_member_propositions');
        }

        return $this->render('member/propositions/index.html.twig', [
            'propositions' => $this->propositionRepository->findByAuteur($user),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_member_propositions_delete', methods: ['POST'])]
    public function delete(Proposition $proposition, Request $request): Response
    {
        // Le voter garantit que seul l'auteur peut supprimer sa proposition.
        $this->denyAccessUnlessGranted(PropositionVoter::DELETE, $proposition);

        if (!$this->isCsrfTokenValid('member_proposition_delete_' . $proposition->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_member_propositions');
        }

        $this->entityManager->remove($proposition);
        $this->entityManager->flush();

        $this->addFlash('success', 'Proposition supprimée.');

        return $this->redirectToRoute('app_member_propositions');
    }
}
