<?php

namespace App\Controller\Admin;


use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Partenaire;
use App\Entity\Podcast;
use App\Entity\Presse;
use App\Entity\Projets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
      
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(PresseCrudController::class)->generateUrl());

       
        
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Les Univers Singuliers')

           
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::subMenu('Articles de Presse', 'fas fa-newspaper')->setSubItems([
            MenuItem::linkToCrud('Tous les Articles de Presse', 'fas fa-newspaper', Presse::class),
            MenuItem::linkToCrud('Ajouter','fas fa-plus', Presse::class)->setAction(Crud::PAGE_NEW)
        ]);
        
        yield MenuItem::subMenu("Projets ", "fa-solid fa-list-check")->setSubItems([
            MenuItem::linkToCrud('Tous les Projets', 'fa-solid fa-list-check', Projets::class),
            MenuItem::linkToCrud('Ajouter','fas fa-plus', Projets::class)->setAction(Crud::PAGE_NEW)
        ]);

        yield MenuItem::section('RESSOURCES');

        yield MenuItem::subMenu("Articles  ", "fa-solid fa-receipt")->setSubItems([
            MenuItem::linkToCrud('Tous les Articles ', 'fas fa-newspaper', Article::class),
            MenuItem::linkToCrud('Ajouter','fas fa-plus', Article::class)->setAction(Crud::PAGE_NEW)
        ]);

        yield MenuItem::subMenu("Partenaires ", "fa-solid fa-user-secret")->setSubItems([
            MenuItem::linkToCrud('Tous les Partenaires', 'fa-solid fa-user-secret', Partenaire::class),
            MenuItem::linkToCrud('Ajouter','fas fa-plus', Partenaire::class)->setAction(Crud::PAGE_NEW)
        ]);

        yield MenuItem::subMenu("Podcast", "fa-solid fa-podcast")->setSubItems([
            MenuItem::linkToCrud('Tous les Podcasts', 'fa-solid fa-podcast', Podcast::class),
            MenuItem::linkToCrud('Ajouter','fas fa-plus', Podcast::class)->setAction(Crud::PAGE_NEW)
        ]);

        yield MenuItem::section('CATEGORIES');

        yield MenuItem::subMenu("Categories", "fas fa-bars")->setSubItems([
            MenuItem::linkToCrud('Toutes les Categories', 'fas fa-eye', Category::class),
            MenuItem::linkToCrud('Ajouter','fas fa-plus', Category::class)->setAction(Crud::PAGE_NEW)
        ]);
    }
}
