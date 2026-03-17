<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminController extends AbstractController
{
    // Donnees complementaires adherents hors schema SQL.
    private const MEMBER_PROFILE_STORAGE_RELATIVE_PATH = 'var/data/member-profiles.json';
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/adherents', name: 'app_admin_adherents', methods: ['GET', 'POST'])]
    public function adherents(Request $request): Response
    {
        $this->ensureDataDirectories();

        if ($request->isMethod('POST')) {
            // Point d'entree unique pour les actions adherents afin de garder une navigation simple.
            $action = (string) $request->request->get('adherent_action');

            match ($action) {
                'create_account' => $this->handleCreateAccount($request),
                'reset_password' => $this->handleResetPassword($request),
                'save_profile' => $this->handleSaveProfile($request),
                default => $this->addFlash('warning', 'Action adherent inconnue.'),
            };

            return $this->redirectToRoute('app_admin_adherents');
        }

        /** @var list<User> $users */
        $users = $this->entityManager->getRepository(User::class)->findBy([], ['email' => 'ASC']);
        $profiles = $this->readEntries($this->getMemberProfilesStoragePath());

        return $this->render('admin/adherents.html.twig', [
            'users' => $users,
            'profiles' => is_array($profiles) ? $profiles : [],
        ]);
    }

    #[Route('/adherents/export.csv', name: 'app_admin_adherents_export', methods: ['GET'])]
    public function adherentsExport(): Response
    {
        $this->ensureDataDirectories();

        /** @var list<User> $users */
        $users = $this->entityManager->getRepository(User::class)->findBy([], ['email' => 'ASC']);
        $profiles = $this->readEntries($this->getMemberProfilesStoragePath());
        if (!is_array($profiles)) {
            $profiles = [];
        }

        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw $this->createNotFoundException('Impossible de generer le fichier CSV.');
        }

        // Le BOM garantit un affichage correct des accents UTF-8 dans les tableurs.
        fputs($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'id',
            'email',
            'role',
            'prenom',
            'nom',
            'telephone',
            'adresse',
            'code_postal',
            'ville',
            'cotisation_statut',
            'cotisation_date',
            'attestation_statut',
            'attestation_date',
            'notes',
            'champs_personnalises',
        ], ';');

        foreach ($users as $user) {
            // Chaque ligne fusionne la table user SQL + la fiche JSON.
            $profile = $profiles[(string) $user->getId()] ?? [];

            fputcsv($stream, [
                (string) ($user->getId() ?? ''),
                $user->getEmail(),
                in_array('ROLE_ADMIN', $user->getRoles(), true) ? 'admin' : 'adherent',
                (string) ($profile['first_name'] ?? ''),
                (string) ($profile['last_name'] ?? ''),
                (string) ($profile['phone'] ?? ''),
                (string) ($profile['address'] ?? ''),
                (string) ($profile['postal_code'] ?? ''),
                (string) ($profile['city'] ?? ''),
                (string) ($profile['cotisation_status'] ?? ''),
                (string) ($profile['cotisation_date'] ?? ''),
                (string) ($profile['attestation_status'] ?? ''),
                (string) ($profile['attestation_date'] ?? ''),
                (string) ($profile['notes'] ?? ''),
                (string) ($profile['extra_fields'] ?? ''),
            ], ';');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if (!is_string($csv)) {
            $csv = '';
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="adherents-' . date('Ymd') . '.csv"'
        );

        return $response;
    }
    #[Route('/adherents/{id}', name: 'app_admin_adherent_edit', methods: ['GET', 'POST'])]
public function adherentEdit(int $id, Request $request): Response
{
    $this->ensureDataDirectories();

    $user = $this->entityManager->getRepository(User::class)->find($id);
    if (!$user instanceof User) {
        $this->addFlash('error', 'Adherent introuvable.');
        return $this->redirectToRoute('app_admin_adherents');
    }

    if ($request->isMethod('POST')) {
        $action = (string) $request->request->get('adherent_action');

        match ($action) {
            'save_profile' => $this->handleSaveProfile($request),
            'reset_password' => $this->handleResetPassword($request),
            default => $this->addFlash('warning', 'Action inconnue.'),
        };

        return $this->redirectToRoute('app_admin_adherent_edit', ['id' => $id]);
    }

    $profiles = $this->readEntries($this->getMemberProfilesStoragePath());
    $profile = $profiles[(string) $id] ?? [];

    return $this->render('admin/adherent_edit.html.twig', [
        'user' => $user,
        'profile' => $profile,
    ]);
}
    #[Route('/publications', name: 'app_admin_publications', methods: ['GET', 'POST'])]
    public function publications(Request $request): Response
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $uploadsDir = $projectDir . '/public/uploads';
        $dataDir = $projectDir . '/var/data';

        // Preparation systematique des dossiers de publication.
        $filesystem = new Filesystem();
        $filesystem->mkdir([
            $uploadsDir . '/planning',
            $uploadsDir . '/comptes-rendus',
            $uploadsDir . '/mooks',
            $dataDir,
        ]);

        // Compatibilite: copie des anciens fichiers "moock" vers le dossier "mook".
    

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
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $messages = $this->readEntries($projectDir . '/var/data/contact-messages.json');

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
        ]);
    }

     #[Route('/messages/{index}', name: 'app_admin_message_show', methods: ['GET'])]
    public function messageShow(int $index): Response
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $storageFile = $projectDir . '/var/data/contact-messages.json';
        $messages = $this->readEntries($storageFile);

        if (!isset($messages[$index])) {
            $this->addFlash('error', 'Message introuvable.');
            return $this->redirectToRoute('app_admin_messages');
        }

        // Marquer comme lu
        if (!($messages[$index]['is_read'] ?? false)) {
            $messages[$index]['is_read'] = true;
            $this->writeEntries($storageFile, $messages);
        }

        return $this->render('admin/message_show.html.twig', [
            'msg' => $messages[$index],
        ]);
    }
    
    private function handleCreateAccount(Request $request): void
    {
        // Creation admin-only: aucune inscription publique n'est exposee.
        if (!$this->isCsrfTokenValid('admin_member_create', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide pour la creation de compte.');

            return;
        }

        $email = mb_strtolower(trim((string) $request->request->get('email', '')));
        $password = (string) $request->request->get('password', '');
        $roleLevel = (string) $request->request->get('role_level', 'member');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Email invalide.');

            return;
        }

        if (mb_strlen($password) < 8) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caracteres.');

            return;
        }

        /** @var User|null $existing */
        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing instanceof User) {
            $this->addFlash('error', 'Un compte existe deja avec cet email.');

            return;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roleLevel === 'admin' ? ['ROLE_ADMIN'] : []);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Compte cree pour ' . $email . '.');
    }

    private function handleResetPassword(Request $request): void
    {
        // Reinitialisation admin uniquement, avec controle de longueur minimale.
        if (!$this->isCsrfTokenValid('admin_member_reset_password', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide pour la reinitialisation.');

            return;
        }

        $userId = (int) $request->request->get('user_id', 0);
        $newPassword = (string) $request->request->get('new_password', '');

        if ($userId <= 0) {
            $this->addFlash('error', 'Adherent introuvable.');

            return;
        }

        if (mb_strlen($newPassword) < 8) {
            $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caracteres.');

            return;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            $this->addFlash('error', 'Compte introuvable.');

            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->entityManager->flush();

        $this->addFlash('success', 'Mot de passe mis a jour pour ' . $user->getEmail() . '.');
    }

    private function handleSaveProfile(Request $request): void
    {
        $userId = (int) $request->request->get('user_id', 0);
        $tokenId = 'admin_member_profile_' . $userId;

        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide pour la fiche adherent.');

            return;
        }

        if ($userId <= 0) {
            $this->addFlash('error', 'Adherent introuvable.');

            return;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            $this->addFlash('error', 'Compte introuvable.');

            return;
        }

        $profiles = $this->readEntries($this->getMemberProfilesStoragePath());
        if (!is_array($profiles)) {
            $profiles = [];
        }

        // Les profils sont stockes en JSON pour ajouter des champs admin sans migration SQL.
        $profile = [
            'first_name' => $this->trimMax((string) $request->request->get('first_name', ''), 80),
            'last_name' => $this->trimMax((string) $request->request->get('last_name', ''), 80),
            'phone' => $this->trimMax((string) $request->request->get('phone', ''), 40),
            'address' => $this->trimMax((string) $request->request->get('address', ''), 180),
            'postal_code' => $this->trimMax((string) $request->request->get('postal_code', ''), 20),
            'city' => $this->trimMax((string) $request->request->get('city', ''), 80),
            'cotisation_status' => $this->trimMax((string) $request->request->get('cotisation_status', ''), 40),
            'cotisation_date' => $this->normalizeDate((string) $request->request->get('cotisation_date', '')),
            'attestation_status' => $this->trimMax((string) $request->request->get('attestation_status', ''), 40),
            'attestation_date' => $this->normalizeDate((string) $request->request->get('attestation_date', '')),
            'notes' => $this->trimMax((string) $request->request->get('notes', ''), 2000),
            'extra_fields' => $this->trimMax((string) $request->request->get('extra_fields', ''), 2000),
            'updated_at' => date('c'),
        ];

        $profiles[(string) $userId] = $profile;
        $this->writeEntries($this->getMemberProfilesStoragePath(), $profiles);

        $roleLevel = (string) $request->request->get('role_level', 'member');
        $user->setRoles($roleLevel === 'admin' ? ['ROLE_ADMIN'] : []);
        $this->entityManager->flush();

        $this->addFlash('success', 'Fiche adherent mise a jour: ' . $user->getEmail() . '.');
    }

    private function handlePlanningUpload(Request $request, string $targetDir): void
    {
        // Upload remplace toujours le planning courant (nom de fichier fixe).
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
        // Compte-rendu versionne par mois (YYYY-MM) dans le nom de fichier.
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
        if (!$this->isCsrfTokenValid('admin_mook', $csrfToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide pour le mook.');

            return;
        }

           $number = (string) ($request->request->get('mook_number', '1'));
        if (!in_array($number, ['1', '2'], true)) {
            $this->addFlash('error', 'Numero de mook invalide.');

            return;
        }

        $file = $request->files->get('mook_pdf');
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

    private function ensureDataDirectories(): void
    {
        // Centralise la creation du dossier de stockage JSON.
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $filesystem = new Filesystem();
        $filesystem->mkdir($projectDir . '/var/data');
    }

    private function getMemberProfilesStoragePath(): string
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        return $projectDir . '/' . self::MEMBER_PROFILE_STORAGE_RELATIVE_PATH;
    }

    private function readEntries(string $filePath): array
    {
        // Lecture tolerante: retourne un tableau vide si le JSON est absent/corrompu.
        if (!file_exists($filePath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($filePath), true);

        return is_array($data) ? $data : [];
    }

    private function writeEntries(string $filePath, array $entries): void
    {
        // Ecriture atomique avec LOCK_EX pour eviter les ecrasements concurrents.
        $filesystem = new Filesystem();
        $filesystem->mkdir(dirname($filePath));

        file_put_contents(
            $filePath,
            (string) json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    private function trimMax(string $value, int $maxLength): string
    {
        $trimmed = trim($value);

        return mb_substr($trimmed, 0, $maxLength);
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

}
