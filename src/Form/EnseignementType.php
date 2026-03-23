<?php

namespace App\Form;

use App\Entity\ClassName;
use App\Entity\Enseignement;
use App\Repository\ClassNameRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnseignementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isBulkCreation = (bool) $options['bulk_creation'];

        $builder
            ->add(
                'teacher',
                TeatchersAutocompleteField::class
            )
            ->add('matter', MatterAutocompleteField::class)
        ;

        if ($isBulkCreation) {
            $builder->add('classNames', EntityType::class, [
                'class' => ClassName::class,
                'choice_label' => 'name',
                'query_builder' => static fn(ClassNameRepository $repository) => $repository->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'label' => 'Classes',
            ]);

            return;
        }

        $builder->add('className', EntityType::class, [
            'class' => ClassName::class,
            'choice_label' => 'name',
            'query_builder' => static fn(ClassNameRepository $repository) => $repository->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
            'label' => 'Classe',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enseignement::class,
            'bulk_creation' => false,
        ]);

        $resolver->setAllowedTypes('bulk_creation', 'bool');
    }
}
