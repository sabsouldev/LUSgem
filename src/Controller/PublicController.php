<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    // Pages publiques institutionnelles (sans authentification).
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('public/home.html.twig');
    }

    #[Route('/fonctionnement', name: 'app_fonctionnement')]
    public function fonctionnement(): Response
    {
        return $this->render('public/fonctionnement.html.twig');
    }

    #[Route('/charte-cadre', name: 'app_charte')]
    public function charte(): Response
    {
        return $this->render('public/charte.html.twig');
    }

    #[Route('/activites', name: 'app_activites')]
    public function activites(): Response
    {
        return $this->render('public/activites.html.twig');
    }

    #[Route('/mooks', name: 'app_mooks')]
    
    public function mooks(): Response
    {
        return $this->render('public/mooks.html.twig');
    }

    #[Route('/blog-newsletter', name: 'app_blog')]
    public function blog(): Response
    {
        // Les newsletters publiques sont lues depuis le stockage JSON.
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $dataDir = $projectDir . '/var/data';

        return $this->render('public/blog.html.twig', [
            'newsletters' => $this->readEntries($dataDir . '/newsletters.json'),
        ]);
    }

     #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contact_form', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');

                return $this->redirectToRoute('app_contact');
            }

            $name = trim((string) $request->request->get('name', ''));
            $email = trim((string) $request->request->get('email', ''));
            $subject = trim((string) $request->request->get('subject', ''));
            $message = trim((string) $request->request->get('message', ''));

            if ($name === '' || $email === '' || $subject === '' || $message === '') {
                $this->addFlash('error', 'Veuillez remplir tous les champs.');

                return $this->redirectToRoute('app_contact');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Adresse email invalide.');

                return $this->redirectToRoute('app_contact');
            }

            $projectDir = (string) $this->getParameter('kernel.project_dir');
            $storageFile = $projectDir . '/var/data/contact-messages.json';

            $entries = $this->readEntries($storageFile);
            array_unshift($entries, [
                'name' => mb_substr($name, 0, 100),
                'email' => mb_substr($email, 0, 150),
                'subject' => mb_substr($subject, 0, 200),
                'message' => mb_substr($message, 0, 4000),
                'sent_at' => date('c'),
                'is_read' => false,
            ]);

            $filesystem = new \Symfony\Component\Filesystem\Filesystem();
            $filesystem->mkdir(dirname($storageFile));
            file_put_contents(
                $storageFile,
                (string) json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                LOCK_EX
            );

            $this->addFlash('success', 'Votre message a bien ete envoye. Nous vous repondrons dans les meilleurs delais.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('public/contact.html.twig');
    }
     private function readEntries(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($filePath), true);

        return is_array($data) ? $data : [];
    }
}
