<?php

namespace App\Controller;
use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/articles')]
class ArticlesController extends AbstractController
{
    #[Route('/', name: 'articles.index')]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('articles/index.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }


//  ajouter un article 

    #[Route('/new', name: 'articles.new', methods: ['GET', 'POST'])]
    public function new(Request $request, ArticleRepository $articleRepository): Response
    { 
        
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
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
        $article->setIllustration($newName);
            $articleRepository->save($article, true);
        

            return $this->redirectToRoute('articles.index');
        }

        return $this->renderForm('articles/new.html.twig', [
            'article' => $article,
            'articleForm' => $form,
        ]);
    }

    //  Vu d'un Article

    #[Route('/articles/{id}', name: 'articles.show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('articles/show.html.twig', [
            'article' => $article,
        ]);
    }

    // modifier un article

    #[Route('/{id}/edit', name: 'articles.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, ArticleRepository $articleRepository): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
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
            $article->setIllustration($newName);
            $articleRepository->save($article, true);
           


            return $this->redirectToRoute('articles.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('articles/edit.html.twig', [
            'article' => $article,
            'articleForm' => $form,
        ]);
    }
 
    //Supprimer un article

    #[Route('delete/{id}', name: 'articles.delete', methods: ['POST'])]
    public function delete(Request $request, Article $article,ArticleRepository $articleRepository, $id): Response
    {

        $articleToDel = $articleRepository->findOneBy(['id' => $id]);
        if ($this->isCsrfTokenValid('delete'.$articleToDel->getId(), $request->request->get('_token'))) {
            $articleRepository->remove($article, true);
            $this->addFlash("success", "L'Article' a été supprimé");
        }

        return $this->redirectToRoute('articles.index', [], Response::HTTP_SEE_OTHER);
    }


}