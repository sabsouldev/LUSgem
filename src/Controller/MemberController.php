<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEMBER')]
#[Route('/adherent')]
final class MemberController extends AbstractController
{
    // Boite de propositions interne (visible admin + auteur).
    private const MEMBER_PROPOSITIONS_STORAGE_RELATIVE_PATH = 'var/data/propositions.json';

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

    #[Route('/propositions', name: 'app_member_propositions', methods: ['GET', 'POST'])]
    public function propositions(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Protection CSRF obligatoire sur les propositions adherents.
            if (!$this->isCsrfTokenValid('member_proposition', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');

                return $this->redirectToRoute('app_member_propositions');
            }

            $topic = trim((string) $request->request->get('topic', ''));
            $proposal = trim((string) $request->request->get('proposal', ''));
            $confidential = (string) $request->request->get('confidential', '') === '1';

            if ($topic === '' || $proposal === '') {
                $this->addFlash('error', 'Le sujet et la proposition sont obligatoires.');

                return $this->redirectToRoute('app_member_propositions');
            }

            $entries = $this->readEntries($this->getPropositionsStoragePath());
            // Les propositions sont stockees en JSON pour un suivi interne leger.
            array_unshift($entries, [
                'id' => $this->createId(),
                'topic' => mb_substr($topic, 0, 160),
                'proposal' => mb_substr($proposal, 0, 4000),
                'confidential' => $confidential,
                'submitted_by' => $this->getUser()?->getUserIdentifier() ?? 'inconnu',
                'submitted_at' => date('c'),
                'status' => 'Nouveau',
            ]);

            $this->writeEntries($this->getPropositionsStoragePath(), $entries);
            $this->addFlash('success', 'Votre proposition a ete transmise.');

            return $this->redirectToRoute('app_member_propositions');
        }

        $currentUserEmail = $this->getUser()?->getUserIdentifier() ?? '';
        $entries = $this->readEntries($this->getPropositionsStoragePath());

        // Chaque adherent ne voit que ses propres propositions.
        $myEntries = array_values(array_filter($entries, static function (array $entry) use ($currentUserEmail): bool {
            return (string) ($entry['submitted_by'] ?? '') === $currentUserEmail;
        }));

        usort($myEntries, static function (array $a, array $b): int {
            return strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
        });

        // Limite d'affichage pour garder la page lisible.
        return $this->render('member/propositions.html.twig', [
            'my_propositions' => array_slice($myEntries, 0, 10),
        ]);
    }

    private function getPropositionsStoragePath(): string
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        return $projectDir . '/' . self::MEMBER_PROPOSITIONS_STORAGE_RELATIVE_PATH;
    }

    private function readEntries(string $filePath): array
    {
        // Retourne [] si fichier manquant ou JSON invalide.
        if (!file_exists($filePath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($filePath), true);

        return is_array($data) ? $data : [];
    }

    private function writeEntries(string $filePath, array $entries): void
    {
        // LOCK_EX protege les ecritures si deux requetes arrivent en meme temps.
        $filesystem = new Filesystem();
        $filesystem->mkdir(dirname($filePath));

        file_put_contents(
            $filePath,
            (string) json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    private function createId(): string
    {
        return str_replace('.', '', uniqid('proposal_', true));
    }
}
