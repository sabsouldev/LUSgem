<?php

namespace App\Controller;

use App\Entity\Podcast;
use App\Form\PodcastType;
use App\Repository\PodcastRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/podcast')]
class PodcastController extends AbstractController
{
    #[Route('/', name: 'podcast.index')]
    public function index(PodcastRepository $podcastRepository): Response
    {
        return $this->render('podcast/index.html.twig', [
            'podcast' => $podcastRepository->findAll(),
        ]);
    }


//  ajouter un article 

    #[Route('/new', name: 'podcast.new', methods: ['GET', 'POST'])]
    public function new(Request $request, PodcastRepository $podcastRepository): Response
    { 
        
        $podcast = new Podcast();
        $form = $this->createForm(PodcastType::class, $podcast);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('illustration')->getData();
            //récupérer le nom de l'image
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            // Créer un nouveau nom 
            $newName= $originalName . uniqid().". ".$file->guessExtension();
            //Envoyer l'image dans le dossier upload
            $file->move($this->getParameter('uploadDirectory'), $newName);
        //ajout de l'image dans mon objet massage.
        $podcast->setIllustration($newName);
            $podcastRepository->save($podcast, true);
        

            return $this->redirectToRoute('podcast.index');
        }

        return $this->renderForm('podcast/new.html.twig', [
            'podcast' => $podcast,
            'podcastForm' => $form,
        ]);
    }

    //  Vu d'un Article

    #[Route('/podcast/{id}', name: 'podcast.show', methods: ['GET'])]
    public function show(Podcast $podcast): Response
    {
        return $this->render('podcast/show.html.twig', [
            'podcast' => $podcast,
        ]);
    }

    // modifier un article

    #[Route('/{id}/edit', name: 'podcast.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Podcast $podcast, PodcastRepository $podcastRepository): Response
    {
        $form = $this->createForm(PodcastType::class, $podcast);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('illustration')->getData();
            //récupérer le nom de l'image
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            // Créer un nouveau nom 
            $newName= $originalName . uniqid().".". $file->guessExtension();
            //Envoyer l'image dans le dossier upload
            $file->move($this->getParameter('uploadDirectory'), $newName);
           //ajout de l'image dans mon objet massage.
            $podcast->setIllustration($newName);
            $podcastRepository->save($podcast, true);
           


            return $this->redirectToRoute('podcast.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('podcast/edit.html.twig', [
            'podcast' => $podcast,
            'podcastForm' => $form,
        ]);
    }
 
    //Supprimer un article

    #[Route('delete/{id}', name: 'podcast.delete', methods: ['POST'])]
    public function delete(Request $request, Podcast $podcast,PodcastRepository $podcastRepository, $id): Response
    {

        $podcastToDel = $podcastRepository->findOneBy(['id' => $id]);
        if ($this->isCsrfTokenValid('delete'.$podcastToDel->getId(), $request->request->get('_token'))) {
            $podcastRepository->remove($podcast, true);
            $this->addFlash("success", "Le projet a été supprimé");
        }

        return $this->redirectToRoute('podcast.index', [], Response::HTTP_SEE_OTHER);
    }


}
