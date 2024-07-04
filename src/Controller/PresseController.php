<?php

namespace App\Controller;

use App\Entity\Presse;
use App\Form\PresseType;
use App\Repository\PresseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;




class PresseController extends AbstractController
{
    #[Route('/presse', name: 'presse.index')]
    public function index(PresseRepository $presseRepository): Response
    {
        return $this->render('presse/index.html.twig', [
            'presse' => $presseRepository->findAll(),
        ]);
    }


//  ajouter un article 

    #[Route('/presse/new', name: 'presse.new', methods: ['GET', 'POST'])]
    public function new(Request $request, PresseRepository $presseRepository): Response
    { 
        
        $presse = new presse();
        $form = $this->createForm(PresseType::class, $presse);
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
        $presse->setIllustration($newName);
            $presseRepository->save($presse, true);
        

            return $this->redirectToRoute('presse.index');
        }

        return $this->renderForm('presse/new.html.twig', [
            'presse' => $presse,
            'presseForm' => $form,
        ]);
    }

    //  Vu d'un Article

    #[Route('/presse/{id}', name: 'presse.show', methods: ['GET'])]
    public function show(Presse $presse): Response
    {
        return $this->render('presse/show.html.twig', [
            'presse' => $presse,
        ]);
    }

    // modifier un article

    #[Route('/{id}/edit', name: 'presse.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Presse $presse, PresseRepository $presseRepository): Response
    {
        $form = $this->createForm(PresseType::class, $presse);
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
            $presse->setIllustration($newName);
            $presseRepository->save($presse, true);
           


            return $this->redirectToRoute('presse.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('presse/edit.html.twig', [
            'presse' => $presse,
            'presseForm' => $form,
        ]);
    }
 
    //Supprimer un article

    #[Route('delete/{id}', name: 'presse.delete', methods: ['POST'])]
    public function delete(Request $request, presse $presse,PresseRepository $presseRepository, $id): Response
    {

        $presseToDel = $presseRepository->findOneBy(['id' => $id]);
        if ($this->isCsrfTokenValid('delete'.$presseToDel->getId(), $request->request->get('_token'))) {
            $presseRepository->remove($presse, true);
            $this->addFlash("success", "Le projet a été supprimé");
        }

        return $this->redirectToRoute('presse.index', [], Response::HTTP_SEE_OTHER);
    }


}
