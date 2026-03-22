<?php

namespace App\Entity;

use App\Entity\Stagiaire;
use App\Repository\SurveillanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SurveillanceRepository::class)]
class Surveillance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'surveillances')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Examen $examen = null;

    #[ORM\ManyToOne(inversedBy: 'surveillances')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Teatchers $enseignant = null;

    #[ORM\ManyToOne(inversedBy: 'surveillances')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Stagiaire $stagiaire = null;

    #[ORM\ManyToOne(inversedBy: 'surveillances')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ClassName $classe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $salle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExamen(): ?Examen
    {
        return $this->examen;
    }

    public function setExamen(?Examen $examen): static
    {
        $this->examen = $examen;

        return $this;
    }

    public function getEnseignant(): ?Teatchers
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Teatchers $enseignant): static
    {
        $this->enseignant = $enseignant;

        if ($enseignant !== null) {
            $this->stagiaire = null;
        }

        return $this;
    }

    public function getStagiaire(): ?Stagiaire
    {
        return $this->stagiaire;
    }

    public function setStagiaire(?Stagiaire $stagiaire): static
    {
        $this->stagiaire = $stagiaire;

        if ($stagiaire !== null) {
            $this->enseignant = null;
        }

        return $this;
    }

    public function getClasse(): ?ClassName
    {
        return $this->classe;
    }

    public function setClasse(?ClassName $classe): static
    {
        $this->classe = $classe;

        return $this;
    }

    public function getSalle(): ?string
    {
        return $this->salle;
    }

    public function setSalle(?string $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getSurveillantFullName(): string
    {
        return $this->enseignant?->getFullName() ?? $this->stagiaire?->getFullName() ?? '';
    }

    public function getSurveillantType(): string
    {
        if ($this->enseignant !== null) {
            return 'enseignant';
        }

        if ($this->stagiaire !== null) {
            return 'stagiaire';
        }

        return '';
    }

    #[Assert\Callback]
    public function validateSingleSupervisor(ExecutionContextInterface $context): void
    {
        if (($this->enseignant === null && $this->stagiaire === null) || ($this->enseignant !== null && $this->stagiaire !== null)) {
            $context->buildViolation('Une surveillance doit être affectée à un enseignant ou à un stagiaire.')
                ->addViolation();
        }
    }
}
