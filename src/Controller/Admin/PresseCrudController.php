<?php

namespace App\Controller\Admin;

use App\Entity\Presse;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class PresseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Presse::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        
            yield TextField::new('titre');
            yield SlugField::new('slug')->setTargetFieldName('titre');
            yield TextEditorField::new('content');
            yield TextField::new('featuredText');
          
            yield UrlField::new('lien');
            yield TextField ::new('illustration');
            yield TextField::new('autheur');
       
    }
    
}
