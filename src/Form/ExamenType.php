<?php

namespace App\Form;

use App\Entity\ClassName;
use App\Entity\Examen;
use App\Entity\Matter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date')
            ->add('heursDebut')
            ->add('heureFin')
            ->add('matiere', EntityType::class, [
                'class' => Matter::class,
                'choice_label' => 'nom',
            ])
            ->add('classe', EntityType::class, [
                'class' => ClassName::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Examen::class,
        ]);
    }
}
