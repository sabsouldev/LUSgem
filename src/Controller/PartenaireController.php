<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PartenaireController extends AbstractController
{
    #[Route('/partenaire', name: 'partenaire.show')]
    public function show(): Response
    {
        return $this->render('partenaire/show.html.twig', [
            'controller_name' => 'PartenaireController',
        ]);
    }
}
