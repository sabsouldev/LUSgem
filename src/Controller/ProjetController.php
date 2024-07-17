<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProjetController extends AbstractController
{
    #[Route('/projet', name: 'projet.show')]
    public function show(): Response
    {
        return $this->render('projet/show.html.twig', [
            'controller_name' => 'ProjetController',
        ]);
    }
}
