<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    #[Route('/moocks', name: 'app_moocks_legacy')]
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

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('public/contact.html.twig');
    }

    private function readEntries(string $filePath): array
    {
        // Fallback vide si le fichier n'existe pas ou est invalide.
        if (!file_exists($filePath)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($filePath), true);

        return is_array($data) ? $data : [];
    }
}
