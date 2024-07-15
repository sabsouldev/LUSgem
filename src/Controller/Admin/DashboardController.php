<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Partenaire;
use App\Entity\Podcast;
use App\Entity\Presse;
use App\Entity\Projets;
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
            ->setTitle('LUSgem')

           
        ;
    }

    public function configureMenuItems(): iterable
    {
      
        yield MenuItem::linkToCrud("Liste d'Articles de presse ", "fa-solid fa-newspaper", Presse::class);
        yield MenuItem::linkToCrud("Partenaires ", "fa-solid fa-user-secret", Partenaire::class);
        yield MenuItem::linkToCrud("Podcast", "fa-solid fa-podcast", Podcast::class);
        yield MenuItem::linkToCrud("Projets ", "fa-solid fa-list-check", Projets::class);
        yield MenuItem::linkToCrud("Liste d'Articles  ", "fa-solid fa-receipt", Article::class);
    }
}
