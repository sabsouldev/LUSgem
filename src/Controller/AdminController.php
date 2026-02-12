<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/adherents', name: 'app_admin_adherents')]
    public function adherents(): Response
    {
        return $this->render('admin/adherents.html.twig');
    }

    #[Route('/publications', name: 'app_admin_publications', methods: ['GET', 'POST'])]
    public function publications(Request $request): Response
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $uploadsDir = $projectDir . '/public/uploads';
        $dataDir = $projectDir . '/var/data';

        $filesystem = new Filesystem();
        $filesystem->mkdir([
            $uploadsDir . '/planning',
            $uploadsDir . '/comptes-rendus',
            $uploadsDir . '/mooks',
            $dataDir,
        ]);

        $this->migrateLegacyMookFiles($uploadsDir);

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('publication_action');

            match ($action) {
                'planning_upload' => $this->handlePlanningUpload($request, $uploadsDir . '/planning'),
                'report_upload' => $this->handleReportUpload($request, $uploadsDir . '/comptes-rendus'),
                'mook_upload', 'moock_upload' => $this->handleMookUpload($request, $uploadsDir . '/mooks'),
                'newsletter_publish' => $this->handleNewsletterPublish($request, $dataDir . '/newsletters.json'),
                default => $this->addFlash('warning', 'Action de publication inconnue.'),
            };

            return $this->redirectToRoute('app_admin_publications');
        }

        $planningFile = file_exists($uploadsDir . '/planning/planning-semaine.pdf') ? '/uploads/planning/planning-semaine.pdf' : null;
        $reports = array_reverse(array_map('basename', glob($uploadsDir . '/comptes-rendus/*.pdf') ?: []));
        $mooks = array_reverse(array_map('basename', glob($uploadsDir . '/mooks/*.pdf') ?: []));
        $newsletters = $this->readEntries($dataDir . '/newsletters.json');

        return $this->render('admin/publications.html.twig', [
            'planning_file' => $planningFile,
            'reports' => $reports,
            'mooks' => $mooks,
            'newsletters' => $newsletters,
        ]);
    }

    #[Route('/messages', name: 'app_admin_messages')]
    public function messages(): Response
    {
        return $this->render('admin/messages.html.twig');
    }

    private function handlePlanningUpload(Request $request, string $targetDir): void
    {
        if (!$this->isCsrfTokenValid('admin_planning', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide pour le planning.');

            return;
        }

        $file = $request->files->get('planning_pdf');
        if (!$file instanceof UploadedFile || strtolower((string) $file->getClientOriginalExtension()) !== 'pdf') {
            $this->addFlash('error', 'Veuillez fournir un fichier PDF valide pour le planning.');

            return;
        }

        $file->move($targetDir, 'planning-semaine.pdf');
        $this->addFlash('success', 'Planning hebdomadaire mis a jour.');
    }

    private function handleReportUpload(Request $request, string $targetDir): void
    {
        if (!$this->isCsrfTokenValid('admin_report', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide pour le compte rendu.');

            return;
        }

        $month = (string) $request->request->get('report_month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->addFlash('error', 'Format de mois invalide. Utiliser YYYY-MM.');

            return;
        }

        $file = $request->files->get('report_pdf');
        if (!$file instanceof UploadedFile || strtolower((string) $file->getClientOriginalExtension()) !== 'pdf') {
            $this->addFlash('error', 'Veuillez fournir un fichier PDF valide pour le compte rendu.');

            return;
        }

        $file->move($targetDir, 'compte-rendu-' . $month . '.pdf');
        $this->addFlash('success', 'Compte rendu publie pour ' . $month . '.');
    }

    private function handleMookUpload(Request $request, string $targetDir): void
    {
        $csrfToken = (string) $request->request->get('_token');
        if (
            !$this->isCsrfTokenValid('admin_mook', $csrfToken)
            && !$this->isCsrfTokenValid('admin_moock', $csrfToken)
        ) {
            $this->addFlash('error', 'Jeton CSRF invalide pour le mook.');

            return;
        }

        $number = (string) ($request->request->get('mook_number') ?? $request->request->get('moock_number', '1'));
        if (!in_array($number, ['1', '2'], true)) {
            $this->addFlash('error', 'Numero de mook invalide.');

            return;
        }

        $file = $request->files->get('mook_pdf');
        if (!$file instanceof UploadedFile) {
            $file = $request->files->get('moock_pdf');
        }
        if (!$file instanceof UploadedFile || strtolower((string) $file->getClientOriginalExtension()) !== 'pdf') {
            $this->addFlash('error', 'Veuillez fournir un fichier PDF valide pour le mook.');

            return;
        }

        $file->move($targetDir, 'mook-' . $number . '.pdf');
        $this->addFlash('success', 'Mook ' . $number . ' mis a jour.');
    }

    private function handleNewsletterPublish(Request $request, string $storageFile): void
    {
        if (!$this->isCsrfTokenValid('admin_newsletter', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide pour la newsletter.');

            return;
        }

        $month = (string) $request->request->get('newsletter_month', date('Y-m'));
        $title = trim((string) $request->request->get('newsletter_title', ''));
        $content = trim((string) $request->request->get('newsletter_content', ''));

        if (!preg_match('/^\d{4}-\d{2}$/', $month) || $title === '' || $content === '') {
            $this->addFlash('error', 'Veuillez remplir le mois, le titre et le contenu de la newsletter.');

            return;
        }

        $entries = $this->readEntries($storageFile);
        array_unshift($entries, [
            'month' => $month,
            'title' => $title,
            'content' => $content,
            'published_at' => date('c'),
        ]);

        $this->writeEntries($storageFile, $entries);
        $this->addFlash('success', 'Newsletter publiee pour ' . $month . '.');
    }

    private function readEntries(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($filePath), true);

        return is_array($data) ? $data : [];
    }

    private function writeEntries(string $filePath, array $entries): void
    {
        file_put_contents(
            $filePath,
            (string) json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    private function migrateLegacyMookFiles(string $uploadsDir): void
    {
        $legacyDir = $uploadsDir . '/moocks';
        $targetDir = $uploadsDir . '/mooks';

        if (!is_dir($legacyDir)) {
            return;
        }

        $legacyFiles = glob($legacyDir . '/*.pdf') ?: [];
        foreach ($legacyFiles as $legacyFilePath) {
            $legacyFilename = basename($legacyFilePath);
            $targetFilename = str_replace('moock-', 'mook-', $legacyFilename);
            $targetPath = $targetDir . '/' . $targetFilename;

            if (!file_exists($targetPath)) {
                @copy($legacyFilePath, $targetPath);
            }
        }
    }

}
