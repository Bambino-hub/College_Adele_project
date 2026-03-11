<?php

namespace App\Entity;

use App\Repository\ClassNameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ClassNameRepository::class)]
class ClassName
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['Enseignement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['Enseignement:read'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Enseignement>
     */
    #[ORM\OneToMany(targetEntity: Enseignement::class, mappedBy: 'className')]
    private Collection $enseignements;

    /**
     * @var Collection<int, Surveillance>
     */
    #[ORM\OneToMany(targetEntity: Surveillance::class, mappedBy: 'classe')]
    private Collection $surveillances;

    /**
     * @var Collection<int, Examen>
     */
    #[ORM\ManyToMany(targetEntity: Examen::class, mappedBy: 'classe')]
    private Collection $examen;

    #[ORM\ManyToOne(inversedBy: 'classNames')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Niveau $niveau = null;


    public function __construct()
    {
        $this->enseignements = new ArrayCollection();
        $this->surveillances = new ArrayCollection();
        $this->examen = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Enseignement>
     */
    public function getEnseignements(): Collection
    {
        return $this->enseignements;
    }

    public function addEnseignement(Enseignement $enseignement): static
    {
        if (!$this->enseignements->contains($enseignement)) {
            $this->enseignements->add($enseignement);
            $enseignement->setClassName($this);
        }

        return $this;
    }

    public function removeEnseignement(Enseignement $enseignement): static
    {
        if ($this->enseignements->removeElement($enseignement)) {
            // set the owning side to null (unless already changed)
            if ($enseignement->getClassName() === $this) {
                $enseignement->setClassName(null);
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
            $surveillance->setClasse($this);
        }

        return $this;
    }

    public function removeSurveillance(Surveillance $surveillance): static
    {
        if ($this->surveillances->removeElement($surveillance)) {
            // set the owning side to null (unless already changed)
            if ($surveillance->getClasse() === $this) {
                $surveillance->setClasse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Examen>
     */
    public function getExamen(): Collection
    {
        return $this->examen;
    }

    public function addExaman(Examen $examan): static
    {
        if (!$this->examen->contains($examan)) {
            $this->examen->add($examan);
            $examan->addClasse($this);
        }

        return $this;
    }

    public function removeExaman(Examen $examan): static
    {
        if ($this->examen->removeElement($examan)) {
            $examan->removeClasse($this);
        }

        return $this;
    }

    public function getNiveau(): ?Niveau
    {
        return $this->niveau;
    }

    public function setNiveau(?Niveau $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }
}
