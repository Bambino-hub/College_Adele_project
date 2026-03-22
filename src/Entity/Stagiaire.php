<?php

namespace App\Entity;

use App\Repository\StagiaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StagiaireRepository::class)]
class Stagiaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de famille est requis')]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est requis')]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est requis')]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'stagiaires')]
    private ?Matter $matiereDeStage = null;

    #[ORM\ManyToOne(inversedBy: 'stagiaires')]
    private ?Teatchers $encadrant = null;

    #[ORM\ManyToOne(inversedBy: 'stagiaires')]
    private ?Cycles $cycle = null;

    /**
     * @var Collection<int, Surveillance>
     */
    #[ORM\OneToMany(targetEntity: Surveillance::class, mappedBy: 'stagiaire')]
    private Collection $surveillances;

    public function __construct()
    {
        $this->surveillances = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMatiereDeStage(): ?Matter
    {
        return $this->matiereDeStage;
    }

    public function setMatiereDeStage(?Matter $matiereDeStage): static
    {
        $this->matiereDeStage = $matiereDeStage;

        return $this;
    }

    public function getEncadrant(): ?Teatchers
    {
        return $this->encadrant;
    }

    public function setEncadrant(?Teatchers $encadrant): static
    {
        $this->encadrant = $encadrant;

        return $this;
    }

    public function getCycle(): ?Cycles
    {
        return $this->cycle;
    }

    public function setCycle(?Cycles $cycle): static
    {
        $this->cycle = $cycle;

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
            $surveillance->setStagiaire($this);
        }

        return $this;
    }

    public function removeSurveillance(Surveillance $surveillance): static
    {
        if ($this->surveillances->removeElement($surveillance)) {
            if ($surveillance->getStagiaire() === $this) {
                $surveillance->setStagiaire(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->lastname, $this->firstname));
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
