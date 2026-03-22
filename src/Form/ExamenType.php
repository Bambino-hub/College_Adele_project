<?php

namespace App\Form;

use App\Entity\ClassName;
use App\Entity\Cycles;
use App\Entity\Examen;
use App\Entity\Matter;
use App\Repository\ClassNameRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Cycles|null $cycle */
        $cycle = $options['cycle'];

        $builder
            ->add('date')
            ->add('heursDebut')
            ->add('heureFin')
            ->add('nombreSurveillantsParClasse', IntegerType::class, [
                'label' => 'Nombre de surveillants par classe',
            ])
            ->add('matiere', EntityType::class, [
                'class' => Matter::class,
                'choice_label' => 'nom',
            ])
            ->add('classe', EntityType::class, [
                'class' => ClassName::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => static function (ClassNameRepository $repository) use ($cycle) {
                    $qb = $repository->createQueryBuilder('c')
                        ->join('c.niveau', 'n')
                        ->leftJoin('n.cycle', 'cycle')
                        ->addSelect('n', 'cycle')
                        ->orderBy('n.name', 'ASC')
                        ->addOrderBy('c.name', 'ASC');

                    if ($cycle !== null) {
                        $qb->andWhere('cycle.id = :cycleId')
                            ->setParameter('cycleId', $cycle->getId());
                    }

                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Examen::class,
            'cycle' => null,
        ]);

        $resolver->setAllowedTypes('cycle', [Cycles::class, 'null']);
    }
}
