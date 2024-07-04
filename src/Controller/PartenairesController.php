<?php

namespace App\Controller;

use App\Entity\Partenaire;
use App\Form\PartenaireType;
use App\Repository\PartenaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/partenaires')]
class PartenairesController extends AbstractController
{
    #[Route('/', name: 'partenaires.index')]
    public function index(PartenaireRepository $partenaireRepository): Response
    {
        return $this->render('partenaires/index.html.twig', [
            'partenaires' => $partenaireRepository->findAll(),
        ]);
    }


//  ajouter un partenaire 

    #[Route('/new', name: 'partenaires.new', methods: ['GET', 'POST'])]
    public function new(Request $request, PartenaireRepository $partenaireRepository): Response
    { 
        
        $partenaire = new Partenaire();
        $form = $this->createForm(PartenaireType::class, $partenaire);
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
        $partenaire->setIllustration($newName);
            $partenaireRepository->save($partenaire, true);
        

            return $this->redirectToRoute('partenaires.index');
        }

        return $this->renderForm('partenaires/new.html.twig', [
            'partenaire' => $partenaire,
            'partenaireForm' => $form,
        ]);
    }

    //  Vu d'un partenaire

    #[Route('/partenaires/{id}', name: 'partenaires.show', methods: ['GET'])]
    public function show(Partenaire $partenaire): Response
    {
        return $this->render('partenaires/show.html.twig', [
            'partenaire' => $partenaire,
        ]);
    }

    // modifier un partenaire

    #[Route('/{id}/edit', name: 'partenaires.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, partenaire $partenaire, PartenaireRepository $partenaireRepository): Response
    {
        $form = $this->createForm(PartenaireType::class, $partenaire);
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
            $partenaire->setIllustration($newName);
            $partenaireRepository->save($partenaire, true);
           


            return $this->redirectToRoute('partenaires.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('partenaires/edit.html.twig', [
            'partenaire' => $partenaire,
            'partenaireForm' => $form,
        ]);
    }
 
    //Supprimer un partenaire

    #[Route('delete/{id}', name: 'partenaires.delete', methods: ['POST'])]
    public function delete(Request $request, Partenaire $partenaire,PartenaireRepository $partenaireRepository, $id): Response
    {

        $partenaireToDel = $partenaireRepository->findOneBy(['id' => $id]);
        if ($this->isCsrfTokenValid('delete'.$partenaireToDel->getId(), $request->request->get('_token'))) {
            $partenaireRepository->remove($partenaire, true);
            $this->addFlash("success", "L'partenaire' a été supprimé");
        }

        return $this->redirectToRoute('partenaires.index', [], Response::HTTP_SEE_OTHER);
    }


}
