<?php

namespace App\Entity;

use App\Repository\TeatchersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeatchersRepository::class)]
class Teatchers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de famille est requis")]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est requis")]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est requis")]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    private ?string $email = null;

    /**
     * @var Collection<int, Enseignement>
     */
    #[ORM\OneToMany(targetEntity: Enseignement::class, mappedBy: 'teacher')]
    private Collection $enseignements;

    public function __construct()
    {
        $this->enseignements = new ArrayCollection();
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
}
