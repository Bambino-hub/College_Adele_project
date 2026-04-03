<?php

namespace App\Form;

use App\Entity\ClassName;
use App\Entity\Cycles;
use App\Entity\Examen;
use App\Entity\Matter;
use App\Repository\ClassNameRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExamenType extends AbstractType
{
    public function __construct(
        private readonly ClassNameRepository $classNameRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Cycles|null $cycle */
        $cycle = $options['cycle'];
        $useGroupedSelection = $cycle !== null;

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
            ]);

        if ($useGroupedSelection) {
            $groupedChoices = $this->buildGroupedClassChoices($cycle);
            $choiceLabels = [];

            foreach (array_keys($groupedChoices) as $label) {
                $choiceLabels[$label] = $label;
            }

            $builder->add('levelTargets', ChoiceType::class, [
                'label' => $cycle->getName() === 'Cycle 1' ? 'Niveaux concernés' : 'Niveaux / séries concernés',
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choices' => $choiceLabels,
                'data' => $this->resolvePreselectedGroups($builder->getData(), $groupedChoices),
                'help' => $cycle->getName() === 'Cycle 1'
                    ? 'Au collège, un niveau sélectionné ajoute automatiquement toutes ses classes (ex. 6eme A, B et C).'
                    : 'Au lycée, les regroupements respectent déjà les séries (ex. 1ere A, 1ere D, 2nde CD).',
            ]);

            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($groupedChoices): void {
                $examen = $event->getData();

                if (!$examen instanceof Examen) {
                    return;
                }

                foreach ($examen->getClasse()->toArray() as $classe) {
                    $examen->removeClasse($classe);
                }

                $selectedGroups = (array) $event->getForm()->get('levelTargets')->getData();

                foreach ($selectedGroups as $groupLabel) {
                    foreach ($groupedChoices[$groupLabel] ?? [] as $classe) {
                        $examen->addClasse($classe);
                    }
                }
            });

            return;
        }

        $builder->add('classe', EntityType::class, [
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
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Examen::class,
            'cycle' => null,
        ]);

        $resolver->setAllowedTypes('cycle', [Cycles::class, 'null']);
    }

    /**
     * @return array<string, ClassName[]>
     */
    private function buildGroupedClassChoices(Cycles $cycle): array
    {
        $classes = $this->classNameRepository->createQueryBuilder('c')
            ->join('c.niveau', 'n')
            ->leftJoin('n.cycle', 'cycle')
            ->leftJoin('c.serie', 's')
            ->addSelect('n', 'cycle', 's')
            ->andWhere('cycle.id = :cycleId')
            ->setParameter('cycleId', $cycle->getId())
            ->orderBy('n.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $groupedChoices = [];

        foreach ($classes as $classe) {
            if (!$classe instanceof ClassName) {
                continue;
            }

            $label = $this->resolveGroupedLabel($classe, $cycle);
            $groupedChoices[$label][] = $classe;
        }

        return $groupedChoices;
    }

    private function resolveGroupedLabel(ClassName $classe, Cycles $cycle): string
    {
        $levelName = trim((string) $classe->getNiveau()?->getName());

        if ($levelName === '') {
            return 'Sans niveau';
        }

        if ($cycle->getName() === 'Cycle 1') {
            return $levelName;
        }

        $serieName = trim((string) $classe->getSerie()?->getName());
        $normalizedLevel = mb_strtolower($levelName);
        $normalizedSerie = mb_strtolower($serieName);

        if ($serieName === '' || str_contains($normalizedLevel, $normalizedSerie)) {
            return $levelName;
        }

        return sprintf('%s - Série %s', $levelName, $serieName);
    }

    /**
     * @param array<string, ClassName[]> $groupedChoices
     * @return string[]
     */
    private function resolvePreselectedGroups(mixed $formData, array $groupedChoices): array
    {
        if (!$formData instanceof Examen) {
            return [];
        }

        $selectedClassIds = [];

        foreach ($formData->getClasse() as $classe) {
            if ($classe->getId() !== null) {
                $selectedClassIds[$classe->getId()] = true;
            }
        }

        $defaults = [];

        foreach ($groupedChoices as $label => $classes) {
            foreach ($classes as $classe) {
                if ($classe->getId() !== null && isset($selectedClassIds[$classe->getId()])) {
                    $defaults[] = $label;
                    break;
                }
            }
        }

        return $defaults;
    }
}
