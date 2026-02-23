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

    public function __construct()
    {
        $this->enseignements = new ArrayCollection();
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
}
