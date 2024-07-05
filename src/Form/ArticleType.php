<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre' , TextType::class, [
                'attr' => [
                    'class' => 'Titre'
                ]
            ])
            ->add('content' , TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Entrer votre article'
                ]
            ])
            ->add('autheur' , TextType::class, [
                'attr' => [
                    'class' => 'Autheur'
                ]
            ])
            ->add('illustration' , FileType::class, [
                'label' => "Ajouter une illustration",
                'mapped' => false,
                'required' => true
            ])
            ->add('lien' , UrlType::class, [
                'attr' => [
                    'placeholder' => "http://www.exemple.com/",
                    'required' => false
                ]
            ])
            ->add('created_at', null, [
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
