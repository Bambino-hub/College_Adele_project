<?php

namespace App\Tests\Service;

use App\Entity\ClassName;
use App\Entity\Cycles;
use App\Entity\Enseignement;
use App\Entity\Examen;
use App\Entity\Matter;
use App\Entity\Niveau;
use App\Entity\Stagiaire;
use App\Entity\Surveillance;
use App\Entity\Teatchers;
use App\Repository\StagiaireRepository;
use App\Repository\TeatchersRepository;
use App\Service\SurveillanceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SurveillanceGeneratorTest extends TestCase
{
    public function testGeneratePrioritizesSubjectTeacherAtSameLevel(): void
    {
        $cycle1 = (new Cycles())->setName('Cycle 1');
        $this->setEntityId($cycle1, 1);
        $niveau3 = (new Niveau())->setName('3eme')->setCycle($cycle1);
        $niveau4 = (new Niveau())->setName('4eme')->setCycle($cycle1);
        $this->setEntityId($niveau3, 31);
        $this->setEntityId($niveau4, 41);

        $classe3 = (new ClassName())->setName('3eme A')->setNiveau($niveau3);

        $math = (new Matter())->setNom('Maths');
        $fr = (new Matter())->setNom('Francais');
        $this->setEntityId($math, 101);
        $this->setEntityId($fr, 102);

        $tier1Teacher = $this->createTeacher(1, 'Ali', 'Math3', Teatchers::PDF_CYCLE_1);
        $tier2Teacher = $this->createTeacher(2, 'Paul', 'Math4', Teatchers::PDF_CYCLE_1);
        $otherTeacher = $this->createTeacher(3, 'Jean', 'Fr3', Teatchers::PDF_CYCLE_1);

        $this->attachTeaching($tier1Teacher, $math, $classe3);
        $this->attachTeaching($tier2Teacher, $math, (new ClassName())->setName('4eme B')->setNiveau($niveau4));
        $this->attachTeaching($otherTeacher, $fr, $classe3);

        $examen = (new Examen())
            ->setDate(new \DateTime('2026-03-24'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $examen->addClasse($classe3);

        $trainee = $this->createTrainee(99, 'Stag', 'One');

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->once())
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->with(Teatchers::PDF_CYCLE_1)
            ->willReturn([$tier2Teacher, $tier1Teacher, $otherTeacher]);
        $teacherRepository
            ->expects($this->atLeastOnce())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->once())
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([$trainee]);
        $stagiaireRepository
            ->expects($this->never())
            ->method('isTraineeBusyDuringExam')
            ->willReturn(false);

        $persisted = [];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$examen]);

        $this->assertCount(1, $persisted);
        $this->assertSame($tier1Teacher, $persisted[0]->getEnseignant());
        $this->assertNull($persisted[0]->getStagiaire());
    }

    public function testGenerateUsesSameSubjectTeacherBeforeTraineeWhenNoSameLevelTeacher(): void
    {
        $cycle1 = (new Cycles())->setName('Cycle 1');
        $this->setEntityId($cycle1, 1);
        $niveau3 = (new Niveau())->setName('3eme')->setCycle($cycle1);
        $niveau4 = (new Niveau())->setName('4eme')->setCycle($cycle1);
        $this->setEntityId($niveau3, 33);
        $this->setEntityId($niveau4, 44);

        $classe3 = (new ClassName())->setName('3eme B')->setNiveau($niveau3);
        $classe4 = (new ClassName())->setName('4eme C')->setNiveau($niveau4);

        $math = (new Matter())->setNom('Maths');
        $this->setEntityId($math, 201);

        $subjectTeacherOtherLevel = $this->createTeacher(12, 'Paul', 'Math4', Teatchers::PDF_CYCLE_1);
        $this->attachTeaching($subjectTeacherOtherLevel, $math, $classe4);

        $examen = (new Examen())
            ->setDate(new \DateTime('2026-03-25'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $examen->addClasse($classe3);

        $trainee = $this->createTrainee(109, 'Stag', 'Two');

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->once())
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([$subjectTeacherOtherLevel]);
        $teacherRepository
            ->expects($this->atLeastOnce())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->once())
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([$trainee]);
        $stagiaireRepository
            ->expects($this->never())
            ->method('isTraineeBusyDuringExam')
            ->willReturn(false);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$examen]);

        $this->assertCount(1, $persisted);
        $this->assertSame($subjectTeacherOtherLevel, $persisted[0]->getEnseignant());
        $this->assertNull($persisted[0]->getStagiaire());
    }

    public function testGenerateAvoidsConsecutiveSlotsForSameSupervisor(): void
    {
        $cycle1 = (new Cycles())->setName('Cycle 1');
        $this->setEntityId($cycle1, 1);

        $niveau3 = (new Niveau())->setName('3eme')->setCycle($cycle1);
        $this->setEntityId($niveau3, 53);

        $classeA = (new ClassName())->setName('3eme A')->setNiveau($niveau3);
        $classeB = (new ClassName())->setName('3eme B')->setNiveau($niveau3);

        $math = (new Matter())->setNom('Maths');
        $this->setEntityId($math, 301);

        $teacherAli = $this->createTeacher(21, 'Ali', 'Math', Teatchers::PDF_CYCLE_1);
        $teacherPaul = $this->createTeacher(22, 'Paul', 'Math', Teatchers::PDF_CYCLE_1);

        $this->attachTeaching($teacherAli, $math, $classeA);
        $this->attachTeaching($teacherPaul, $math, $classeB);

        // Charge historique artificielle pour Paul, afin que le premier examen
        // sélectionne Ali et que le deuxième retente Ali si la règle de créneau
        // successif n'est pas appliquée.
        $teacherPaul->addSurveillance(new Surveillance());

        $exam1 = (new Examen())
            ->setDate(new \DateTime('2026-03-26'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $exam1->addClasse($classeA);

        $exam2 = (new Examen())
            ->setDate(new \DateTime('2026-03-26'))
            ->setHeursDebut(new \DateTime('09:30:00'))
            ->setHeureFin(new \DateTime('11:30:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $exam2->addClasse($classeB);

        $trainee = $this->createTrainee(209, 'Stag', 'Three');

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->exactly(2))
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->with(Teatchers::PDF_CYCLE_1)
            ->willReturn([$teacherAli, $teacherPaul]);
        $teacherRepository
            ->expects($this->atLeastOnce())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->exactly(2))
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([$trainee]);
        $stagiaireRepository
            ->expects($this->never())
            ->method('isTraineeBusyDuringExam')
            ->willReturn(false);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$exam2, $exam1]);

        $this->assertCount(2, $persisted);
        $this->assertSame($teacherAli, $persisted[0]->getEnseignant());
        $this->assertSame($teacherPaul, $persisted[1]->getEnseignant());
        $this->assertNull($persisted[0]->getStagiaire());
        $this->assertNull($persisted[1]->getStagiaire());
    }

    public function testGenerateAvoidsOverlappingTimesForSameSupervisor(): void
    {
        $cycle1 = (new Cycles())->setName('Cycle 1');
        $this->setEntityId($cycle1, 1);

        $niveau3 = (new Niveau())->setName('3eme')->setCycle($cycle1);
        $this->setEntityId($niveau3, 63);

        $classeA = (new ClassName())->setName('3eme A')->setNiveau($niveau3);
        $classeB = (new ClassName())->setName('3eme B')->setNiveau($niveau3);

        $math = (new Matter())->setNom('Maths');
        $this->setEntityId($math, 401);

        $teacherAli = $this->createTeacher(31, 'Ali', 'Math', Teatchers::PDF_CYCLE_1);
        $teacherPaul = $this->createTeacher(32, 'Paul', 'Math', Teatchers::PDF_CYCLE_1);

        $this->attachTeaching($teacherAli, $math, $classeA);
        $this->attachTeaching($teacherPaul, $math, $classeB);

        // Charge historique pour favoriser Ali sur le premier examen.
        $teacherPaul->addSurveillance(new Surveillance());

        // Examens avec chevauchement: 07:00-09:00 et 08:30-10:30
        $exam1 = (new Examen())
            ->setDate(new \DateTime('2026-03-27'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $exam1->addClasse($classeA);

        $exam2 = (new Examen())
            ->setDate(new \DateTime('2026-03-27'))
            ->setHeursDebut(new \DateTime('08:30:00'))
            ->setHeureFin(new \DateTime('10:30:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $exam2->addClasse($classeB);

        $trainee = $this->createTrainee(309, 'Stag', 'Four');

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->exactly(2))
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->with(Teatchers::PDF_CYCLE_1)
            ->willReturn([$teacherAli, $teacherPaul]);
        $teacherRepository
            ->expects($this->atLeastOnce())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->exactly(2))
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([$trainee]);
        $stagiaireRepository
            ->expects($this->never())
            ->method('isTraineeBusyDuringExam')
            ->willReturn(false);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$exam2, $exam1]);

        $this->assertCount(2, $persisted);
        $this->assertSame($teacherAli, $persisted[0]->getEnseignant());
        $this->assertSame($teacherPaul, $persisted[1]->getEnseignant());
        $this->assertNull($persisted[0]->getStagiaire());
        $this->assertNull($persisted[1]->getStagiaire());
    }

    public function testGenerateKeepsSameSupervisorForTleCAndTleD1AcrossSeparateExams(): void
    {
        $cycle2 = (new Cycles())->setName('Cycle 2');
        $this->setEntityId($cycle2, 2);

        $niveauC = (new Niveau())->setName('Tle C')->setCycle($cycle2);
        $niveauD1 = (new Niveau())->setName('Tle D1')->setCycle($cycle2);
        $this->setEntityId($niveauC, 81);
        $this->setEntityId($niveauD1, 82);

        $classeC = (new ClassName())->setName('Tle C')->setNiveau($niveauC);
        $classeD1 = (new ClassName())->setName('Tle D1')->setNiveau($niveauD1);

        $math = (new Matter())->setNom('Maths');
        $svt = (new Matter())->setNom('SVT');
        $this->setEntityId($math, 601);
        $this->setEntityId($svt, 602);

        $teacherAli = $this->createTeacher(51, 'Ali', 'Shared', Teatchers::PDF_CYCLE_2);
        $teacherPaul = $this->createTeacher(52, 'Paul', 'Backup', Teatchers::PDF_CYCLE_2);

        $this->attachTeaching($teacherAli, $math, $classeC);
        $this->attachTeaching($teacherPaul, $svt, $classeD1);

        $examC = (new Examen())
            ->setDate(new \DateTime('2026-03-29'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $examC->addClasse($classeC);
        $this->setEntityId($examC, 1);

        $examD1 = (new Examen())
            ->setDate(new \DateTime('2026-03-29'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($svt)
            ->setNombreSurveillantsParClasse(1);
        $examD1->addClasse($classeD1);
        $this->setEntityId($examD1, 2);

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->exactly(2))
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->with(Teatchers::PDF_CYCLE_2)
            ->willReturn([$teacherAli, $teacherPaul]);
        $teacherRepository
            ->expects($this->atLeastOnce())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->exactly(2))
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$examD1, $examC]);

        $this->assertCount(2, $persisted);
        $this->assertSame($teacherAli, $persisted[0]->getEnseignant());
        $this->assertSame($teacherAli, $persisted[1]->getEnseignant());
        $this->assertSame('Tle C', $persisted[0]->getClasse()?->getName());
        $this->assertSame('Tle D1', $persisted[1]->getClasse()?->getName());
    }

    public function testGenerateAssignsDifferentSupervisorToTleD2WhenTleCAndTleD1AreAlsoPresent(): void
    {
        $cycle2 = (new Cycles())->setName('Cycle 2');
        $this->setEntityId($cycle2, 2);

        $niveauC = (new Niveau())->setName('Tle C')->setCycle($cycle2);
        $niveauD1 = (new Niveau())->setName('Tle D1')->setCycle($cycle2);
        $niveauD2 = (new Niveau())->setName('Tle D2')->setCycle($cycle2);
        $this->setEntityId($niveauC, 83);
        $this->setEntityId($niveauD1, 84);
        $this->setEntityId($niveauD2, 85);

        $classeC = (new ClassName())->setName('Tle C')->setNiveau($niveauC);
        $classeD1 = (new ClassName())->setName('Tle D1')->setNiveau($niveauD1);
        $classeD2 = (new ClassName())->setName('Tle D2')->setNiveau($niveauD2);

        $math = (new Matter())->setNom('Maths');
        $this->setEntityId($math, 603);

        $teacherAli = $this->createTeacher(53, 'Ali', 'Shared', Teatchers::PDF_CYCLE_2);
        $teacherPaul = $this->createTeacher(54, 'Paul', 'Separate', Teatchers::PDF_CYCLE_2);

        $this->attachTeaching($teacherAli, $math, $classeC);
        $this->attachTeaching($teacherPaul, $math, $classeD2);

        $exam = (new Examen())
            ->setDate(new \DateTime('2026-03-29'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($math)
            ->setNombreSurveillantsParClasse(1);
        $exam->addClasse($classeC);
        $exam->addClasse($classeD1);
        $exam->addClasse($classeD2);

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->once())
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->with(Teatchers::PDF_CYCLE_2)
            ->willReturn([$teacherAli, $teacherPaul]);
        $teacherRepository
            ->expects($this->atLeastOnce())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->once())
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$exam]);

        $this->assertCount(3, $persisted);
        $this->assertSame($teacherAli, $persisted[0]->getEnseignant());
        $this->assertSame($teacherAli, $persisted[1]->getEnseignant());
        $this->assertSame($teacherPaul, $persisted[2]->getEnseignant());
        $this->assertNotSame($persisted[0]->getEnseignant(), $persisted[2]->getEnseignant());
    }

    public function testGeneratePrefersTraineeBeforeDomainTeacherWhenNoSubjectSpecialist(): void
    {
        $cycle1 = (new Cycles())->setName('Cycle 1');
        $this->setEntityId($cycle1, 1);

        $niveau3 = (new Niveau())->setName('3eme')->setCycle($cycle1);
        $this->setEntityId($niveau3, 72);

        $classe3 = (new ClassName())->setName('3eme D')->setNiveau($niveau3);

        $svt = (new Matter())->setNom('SVT');
        $math = (new Matter())->setNom('MATHS');
        $this->setEntityId($svt, 491);
        $this->setEntityId($math, 492);

        $scientificTeacher = $this->createTeacher(40, 'Kouassi', 'Math', Teatchers::PDF_CYCLE_1);
        $this->attachTeaching($scientificTeacher, $math, $classe3);

        $trainee = $this->createTrainee(409, 'Stag', 'Priority');
        $trainee->setMatiereDeStage($math);

        $exam = (new Examen())
            ->setDate(new \DateTime('2026-03-28'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($svt)
            ->setNombreSurveillantsParClasse(1);
        $exam->addClasse($classe3);

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->once())
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->with(Teatchers::PDF_CYCLE_1)
            ->willReturn([$scientificTeacher]);
        $teacherRepository
            ->expects($this->never())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->once())
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([$trainee]);
        $stagiaireRepository
            ->expects($this->atLeastOnce())
            ->method('isTraineeBusyDuringExam')
            ->willReturn(false);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$exam]);

        $this->assertCount(1, $persisted);
        $this->assertSame($trainee, $persisted[0]->getStagiaire());
        $this->assertNull($persisted[0]->getEnseignant());
    }

    public function testGenerateFallsBackToLiteraryDomainTeacherWhenNoSubjectSpecialist(): void
    {
        $cycle1 = (new Cycles())->setName('Cycle 1');
        $this->setEntityId($cycle1, 1);

        $niveau3 = (new Niveau())->setName('3eme')->setCycle($cycle1);
        $this->setEntityId($niveau3, 73);

        $classe3 = (new ClassName())->setName('3eme C')->setNiveau($niveau3);

        $philo = (new Matter())->setNom('PHILO');
        $fr = (new Matter())->setNom('FR');
        $math = (new Matter())->setNom('MATHS');
        $this->setEntityId($philo, 501);
        $this->setEntityId($fr, 502);
        $this->setEntityId($math, 503);

        $literaryTeacher = $this->createTeacher(41, 'Karka', 'Fr', Teatchers::PDF_CYCLE_1);
        $scientificTeacher = $this->createTeacher(42, 'Kondo', 'Math', Teatchers::PDF_CYCLE_1);

        $this->attachTeaching($literaryTeacher, $fr, $classe3);
        $this->attachTeaching($scientificTeacher, $math, $classe3);

        $exam = (new Examen())
            ->setDate(new \DateTime('2026-03-28'))
            ->setHeursDebut(new \DateTime('07:00:00'))
            ->setHeureFin(new \DateTime('09:00:00'))
            ->setMatiere($philo)
            ->setNombreSurveillantsParClasse(1);
        $exam->addClasse($classe3);

        $teacherRepository = $this->createMock(TeatchersRepository::class);
        $teacherRepository
            ->expects($this->once())
            ->method('findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount')
            ->with(Teatchers::PDF_CYCLE_1)
            ->willReturn([$scientificTeacher, $literaryTeacher]);
        $teacherRepository
            ->expects($this->atLeastOnce())
            ->method('isTeacherBusyDuringExam')
            ->willReturn(false);

        $stagiaireRepository = $this->createMock(StagiaireRepository::class);
        $stagiaireRepository
            ->expects($this->once())
            ->method('findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount')
            ->willReturn([]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            if ($entity instanceof Surveillance) {
                $persisted[] = $entity;
            }
        });
        $em->method('remove');
        $em->expects($this->exactly(2))->method('flush');

        $generator = new SurveillanceGenerator($teacherRepository, $stagiaireRepository, $em);
        $generator->generate([$exam]);

        $this->assertCount(1, $persisted);
        $this->assertSame($literaryTeacher, $persisted[0]->getEnseignant());
        $this->assertNull($persisted[0]->getStagiaire());
    }

    private function createTeacher(int $id, string $lastname, string $firstname, string $pdfCycle): Teatchers
    {
        $teacher = (new Teatchers())
            ->setLastname($lastname)
            ->setFirstname($firstname)
            ->setPhoneNumber('90000000')
            ->setPdfCycle($pdfCycle)
            ->setCanSupervise(true);

        $this->setEntityId($teacher, $id);

        return $teacher;
    }

    private function createTrainee(int $id, string $lastname, string $firstname): Stagiaire
    {
        $trainee = (new Stagiaire())
            ->setLastname($lastname)
            ->setFirstname($firstname)
            ->setPhoneNumber('91000000')
            ->setEmail('stagiaire@example.test');

        $this->setEntityId($trainee, $id);

        return $trainee;
    }

    private function attachTeaching(Teatchers $teacher, Matter $matter, ClassName $class): void
    {
        $enseignement = (new Enseignement())
            ->setTeacher($teacher)
            ->setMatter($matter)
            ->setClassName($class);

        $teacher->addEnseignement($enseignement);
        $class->addEnseignement($enseignement);
        $matter->addEnseignement($enseignement);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
