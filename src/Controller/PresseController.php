<?php

namespace App\Controller;

use App\Entity\Presse;
use App\Form\PresseType;
use App\Repository\PresseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/presse')]
class PresseController extends AbstractController
{
    #[Route('/', name: 'presse.index', methods: ['GET'])]
    public function index(PresseRepository $presseRepository): Response
    {
        return $this->render('presse/index.html.twig', [
            'presses' => $presseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'presse.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $presse = new Presse();
        $form = $this->createForm(PresseType::class, $presse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($presse);
            $entityManager->flush();

            return $this->redirectToRoute('app_presse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('presse/new.html.twig', [
            'presse' => $presse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'presse.show', methods: ['GET'])]
    public function show(Presse $presse): Response
    {
        return $this->render('presse/show.html.twig', [
            'presse' => $presse,
        ]);
    }

    #[Route('/{id}/edit', name: 'presse.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Presse $presse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PresseType::class, $presse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('presse.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('presse/edit.html.twig', [
            'presse' => $presse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'presse.delete', methods: ['POST'])]
    public function delete(Request $request, Presse $presse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$presse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($presse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('presse.index', [], Response::HTTP_SEE_OTHER);
    }
}
