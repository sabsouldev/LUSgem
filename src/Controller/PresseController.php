<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PresseController extends AbstractController
{
    #[Route('/presse', name: 'presse.show')]
    public function show(): Response
    {
        return $this->render('presse/show.html.twig', [
            'controller_name' => 'PresseController',
        ]);
    }
}
