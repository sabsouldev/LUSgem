<?php

namespace App\Form;

use App\Entity\Presse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PresseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('content')
            ->add('author')
            ->add('createdat', null, [
                'widget' => 'single_text',
            ])
            ->add('photo')
            ->add('fichier')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Presse::class,
        ]);
    }
}
