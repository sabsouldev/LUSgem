<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PodcastController extends AbstractController
{
    #[Route('/podcast', name: 'podcast.show')]
    public function show(): Response
    {
        return $this->render('podcast/show.html.twig', [
            'controller_name' => 'PodcastController',
        ]);
    }
}
