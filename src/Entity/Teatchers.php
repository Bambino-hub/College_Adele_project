<?php

namespace App\Entity;

use App\Entity\Stagiaire;
use App\Repository\TeatchersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeatchersRepository::class)]
class Teatchers
{
    public const PDF_CYCLE_1 = '1';
    public const PDF_CYCLE_2 = '2';
    public const PDF_CYCLE_BOTH = '1/2';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['Enseignement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de famille est requis")]
    #[Groups(['Enseignement:read'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est requis")]
    #[Groups(['Enseignement:read'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est requis")]
    #[Groups(['Enseignement:read'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['Enseignement:read'])]
    private ?string $sexe = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['Enseignement:read'])]
    private ?string $matricule = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['Enseignement:read'])]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $disciplines = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $pdfCycle = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $canSupervise = true;

    /**
     * @var Collection<int, Enseignement>
     */
    #[ORM\OneToMany(targetEntity: Enseignement::class, mappedBy: 'teacher')]
    private Collection $enseignements;

    /**
     * @var Collection<int, Surveillance>
     */
    #[ORM\OneToMany(targetEntity: Surveillance::class, mappedBy: 'enseignant')]
    private Collection $surveillances;

    /**
     * @var Collection<int, Stagiaire>
     */
    #[ORM\OneToMany(targetEntity: Stagiaire::class, mappedBy: 'encadrant')]
    private Collection $stagiaires;

    public function __construct()
    {
        $this->enseignements = new ArrayCollection();
        $this->surveillances = new ArrayCollection();
        $this->stagiaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDisciplines(): ?string
    {
        return $this->disciplines;
    }

    public function setDisciplines(?string $disciplines): static
    {
        $this->disciplines = $disciplines;

        return $this;
    }

    public function getPdfCycle(): ?string
    {
        return $this->pdfCycle;
    }

    public function setPdfCycle(?string $pdfCycle): static
    {
        $this->pdfCycle = $pdfCycle;

        return $this;
    }

    public function isCanSupervise(): bool
    {
        return $this->canSupervise;
    }

    public function setCanSupervise(bool $canSupervise): static
    {
        $this->canSupervise = $canSupervise;

        return $this;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->lastname, $this->firstname));
    }

    public function teachesInCycle(Cycles $cycle): bool
    {
        foreach ($this->enseignements as $enseignement) {
            if ($enseignement->getClassName()?->getNiveau()?->getCycle()?->getId() === $cycle->getId()) {
                return true;
            }
        }

        return false;
    }

    public function teachesMatterInCycle(Matter $matter, Cycles $cycle): bool
    {
        foreach ($this->enseignements as $enseignement) {
            if (
                $enseignement->getMatter()?->getId() === $matter->getId()
                && $enseignement->getClassName()?->getNiveau()?->getCycle()?->getId() === $cycle->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'enseignant enseigne une matière donnée à un niveau précis.
     * Utilisé pour prioriser la surveillance par niveau effectivement enseigné.
     */
    public function teachesMatterInNiveau(Matter $matter, ?Niveau $niveau): bool
    {
        if ($niveau === null || $niveau->getId() === null) {
            return false;
        }

        foreach ($this->enseignements as $enseignement) {
            if (
                $enseignement->getMatter()?->getId() === $matter->getId()
                && $enseignement->getClassName()?->getNiveau()?->getId() === $niveau->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    /**
     * @return Collection<int, Enseignement>
     */
    public function getEnseignement(): Collection
    {
        return $this->enseignements;
    }

    public function addEnseignement(Enseignement $enseignements): static
    {
        if (!$this->enseignements->contains($enseignements)) {
            $this->enseignements->add($enseignements);
            $enseignements->setTeacher($this);
        }

        return $this;
    }

    public function removeEnseignement(Enseignement $enseignements): static
    {
        if ($this->enseignements->removeElement($enseignements)) {
            // set the owning side to null (unless already changed)
            if ($enseignements->getTeacher() === $this) {
                $enseignements->setTeacher(null);
            }
        }

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
            $surveillance->setEnseignant($this);
        }

        return $this;
    }

    public function removeSurveillance(Surveillance $surveillance): static
    {
        if ($this->surveillances->removeElement($surveillance)) {
            // set the owning side to null (unless already changed)
            if ($surveillance->getEnseignant() === $this) {
                $surveillance->setEnseignant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Stagiaire>
     */
    public function getStagiaires(): Collection
    {
        return $this->stagiaires;
    }

    public function addStagiaire(Stagiaire $stagiaire): static
    {
        if (!$this->stagiaires->contains($stagiaire)) {
            $this->stagiaires->add($stagiaire);
            $stagiaire->setEncadrant($this);
        }

        return $this;
    }

    public function removeStagiaire(Stagiaire $stagiaire): static
    {
        if ($this->stagiaires->removeElement($stagiaire)) {
            if ($stagiaire->getEncadrant() === $this) {
                $stagiaire->setEncadrant(null);
            }
        }

        return $this;
    }
}
