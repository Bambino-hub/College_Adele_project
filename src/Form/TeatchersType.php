<?php

namespace App\Form;

use App\Entity\Teatchers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeatchersType extends AbstractType
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
                'label' => 'Contact',
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'F' => 'F',
                    'M' => 'M',
                ],
                'placeholder' => 'Non renseigné',
                'required' => false,
            ])
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'required' => false,
            ])
            ->add('statut', TextType::class, [
                'label' => 'Statut',
                'required' => false,
            ])
            ->add('disciplines', TextType::class, [
                'label' => 'Disciplines',
                'required' => false,
            ])
            ->add('pdfCycle', ChoiceType::class, [
                'label' => 'Cycle (PDF)',
                'choices' => [
                    'Cycle 1' => Teatchers::PDF_CYCLE_1,
                    'Cycle 2' => Teatchers::PDF_CYCLE_2,
                    'Cycle 1 et 2' => Teatchers::PDF_CYCLE_BOTH,
                ],
                'placeholder' => 'Choisir le cycle',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Teatchers::class,
        ]);
    }
}
