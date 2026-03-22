<?php

namespace App\Form;

use App\Entity\Cycles;
use App\Entity\Matter;
use App\Entity\Stagiaire;
use App\Entity\Teatchers;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StagiaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Téléphone',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('cycle', EntityType::class, [
                'class' => Cycles::class,
                'choice_label' => 'name',
                'label' => 'Cycle du stage',
            ])
            ->add('matiereDeStage', EntityType::class, [
                'class' => Matter::class,
                'choice_label' => 'nom',
                'label' => 'Matière du stage',
            ])
            ->add('encadrant', EntityType::class, [
                'class' => Teatchers::class,
                'choice_label' => static fn(Teatchers $teacher): string => $teacher->getFullName(),
                'label' => 'Enseignant encadrant',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stagiaire::class,
        ]);
    }
}
