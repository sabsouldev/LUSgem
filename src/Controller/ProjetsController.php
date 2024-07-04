<?php

namespace App\Controller;

use App\Entity\Projets;
use App\Form\ProjetsType;
use App\Repository\ProjetsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projets')]
class ProjetsController extends AbstractController
{
    #[Route('/', name: 'projets.index')]
    public function index(ProjetsRepository $projetsRepository): Response
    {
        return $this->render('projets/index.html.twig', [
            'projets' => $projetsRepository->findAll(),
        ]);
    }


//  ajouter un article 

    #[Route('/new', name: 'projets.new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProjetsRepository $projetsRepository): Response
    { 
        
        $projets = new Projets();
        $form = $this->createForm(ProjetsType::class, $projets);
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
        $projets->setIllustration($newName);
            $projetsRepository->save($projets, true);
        

            return $this->redirectToRoute('projets.index');
        }

        return $this->renderForm('projets/new.html.twig', [
            'projets' => $projets,
            'projetsForm' => $form,
        ]);
    }

    //  Vu d'un Article

    #[Route('/projets/{id}', name: 'projets.show', methods: ['GET'])]
    public function show(Projets $projets): Response
    {
        return $this->render('projets/show.html.twig', [
            'projets' => $projets,
        ]);
    }

    // modifier un article

    #[Route('/{id}/edit', name: 'projets.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Projets $projets, ProjetsRepository $projetsRepository): Response
    {
        $form = $this->createForm(ProjetsType::class, $projets);
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
            $projets->setIllustration($newName);
            $projetsRepository->save($projets, true);
           


            return $this->redirectToRoute('projets.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('projets/edit.html.twig', [
            'projets' => $projets,
            'projetsForm' => $form,
        ]);
    }
 
    //Supprimer un article

    #[Route('delete/{id}', name: 'projets.delete', methods: ['POST'])]
    public function delete(Request $request, Projets $projets,ProjetsRepository $projetsRepository, $id): Response
    {

        $projetsToDel = $projetsRepository->findOneBy(['id' => $id]);
        if ($this->isCsrfTokenValid('delete'.$projetsToDel->getId(), $request->request->get('_token'))) {
            $projetsRepository->remove($projets, true);
            $this->addFlash("success", "Le projet a été supprimé");
        }

        return $this->redirectToRoute('projets.index', [], Response::HTTP_SEE_OTHER);
    }


}
