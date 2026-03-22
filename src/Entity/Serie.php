<?php

namespace App\Entity;

use App\Repository\SerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    private ?Cycles $cycle = null;

    /**
     * @var Collection<int, ClassName>
     */
    #[ORM\OneToMany(targetEntity: ClassName::class, mappedBy: 'serie')]
    private Collection $classNames;

    public function __construct()
    {
        $this->classNames = new ArrayCollection();
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
     * @return Collection<int, ClassName>
     */
    public function getClassNames(): Collection
    {
        return $this->classNames;
    }

    public function addClassName(ClassName $className): static
    {
        if (!$this->classNames->contains($className)) {
            $this->classNames->add($className);
            $className->setSerie($this);
        }

        return $this;
    }

    public function removeClassName(ClassName $className): static
    {
        if ($this->classNames->removeElement($className)) {
            // set the owning side to null (unless already changed)
            if ($className->getSerie() === $this) {
                $className->setSerie(null);
            }
        }

        return $this;
    }
}
