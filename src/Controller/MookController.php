<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MookController extends AbstractController
{
    #[Route('/mook', name: 'mook.index')]
    public function index(): Response
    {
        return $this->render('mook/index.html.twig', [
            'controller_name' => 'MookController',
        ]);
    }
}
