<?php

namespace App\Entity;

use App\Repository\SurveillanceRepository;
use Doctrine\ORM\Mapping as ORM;

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
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Teatchers $enseignant = null;

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
}
