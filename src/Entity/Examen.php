<?php

namespace App\Entity;

use App\Repository\ExamenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ExamenRepository::class)]
class Examen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $nombreSurveillantsParClasse = 1;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heursDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heureFin = null;

    #[ORM\ManyToOne(inversedBy: 'examens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Matter $matiere = null;

    /**
     * @var Collection<int, Surveillance>
     */
    #[ORM\OneToMany(targetEntity: Surveillance::class, mappedBy: 'examen')]
    private Collection $surveillances;

    /**
     * @var Collection<int, ClassName>
     */
    #[ORM\ManyToMany(targetEntity: ClassName::class, inversedBy: 'examen')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Collection $classe;

    public function __construct()
    {
        $this->surveillances = new ArrayCollection();
        $this->classe = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getHeursDebut(): ?\DateTime
    {
        return $this->heursDebut;
    }

    public function setHeursDebut(\DateTime $heursDebut): static
    {
        $this->heursDebut = $heursDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTime
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTime $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function getMatiere(): ?Matter
    {
        return $this->matiere;
    }

    public function setMatiere(?Matter $matiere): static
    {
        $this->matiere = $matiere;

        return $this;
    }

    /**
     * @return Collection<int, Surveillance>
     */
    public function getSurveillances(): Collection
    {
        return $this->surveillances;
    }

    public function addSurveillance(Surveillance $surveillance): static
    {
        if (!$this->surveillances->contains($surveillance)) {
            $this->surveillances->add($surveillance);
            $surveillance->setExamen($this);
        }

        return $this;
    }

    public function removeSurveillance(Surveillance $surveillance): static
    {
        if ($this->surveillances->removeElement($surveillance)) {
            // set the owning side to null (unless already changed)
            if ($surveillance->getExamen() === $this) {
                $surveillance->setExamen(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClassName>
     */
    public function getClasse(): Collection
    {
        return $this->classe;
    }

    public function addClasse(ClassName $classe): static
    {
        if (!$this->classe->contains($classe)) {
            $this->classe->add($classe);
        }

        return $this;
    }

    public function removeClasse(ClassName $classe): static
    {
        $this->classe->removeElement($classe);

        return $this;
    }

    /**
     * Get the value of nombreSurveillantsParClasse
     */
    public function getNombreSurveillantsParClasse(): int
    {
        return $this->nombreSurveillantsParClasse;
    }

    /**
     * Set the value of nombreSurveillantsParClasse
     *
     * @return  self
     */
    public function setNombreSurveillantsParClasse($nombreSurveillantsParClasse): static
    {
        $this->nombreSurveillantsParClasse = $nombreSurveillantsParClasse;

        return $this;
    }

    public function getCycle(): ?Cycles
    {
        foreach ($this->classe as $classe) {
            $cycle = $classe->getNiveau()?->getCycle();

            if ($cycle !== null) {
                return $cycle;
            }
        }

        return null;
    }

    #[Assert\Callback]
    public function validateSingleCycle(ExecutionContextInterface $context): void
    {
        $cycleId = null;

        foreach ($this->classe as $classe) {
            $currentCycle = $classe->getNiveau()?->getCycle();

            if ($currentCycle === null) {
                continue;
            }

            if ($cycleId === null) {
                $cycleId = $currentCycle->getId();
                continue;
            }

            if ($cycleId !== $currentCycle->getId()) {
                $context->buildViolation("Un examen ne peut contenir que des classes d'un seul cycle.")
                    ->atPath('classe')
                    ->addViolation();

                return;
            }
        }
    }
}
