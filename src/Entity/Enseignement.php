<?php

namespace App\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use App\Repository\EnseignementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnseignementRepository::class)]
class Enseignement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['Enseignement:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'className')]
    #[Groups(['Enseignement:read'])]
    private ?Teatchers $teacher = null;

    #[ORM\ManyToOne(inversedBy: 'enseignements')]
    #[Groups(['Enseignement:read'])]
    private ?ClassName $className = null;

    #[ORM\ManyToOne(inversedBy: 'enseignements')]
    #[Groups(['Enseignement:read'])]
    private ?Matter $matter = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeacher(): ?Teatchers
    {
        return $this->teacher;
    }

    public function setTeacher(?Teatchers $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getClassName(): ?ClassName
    {
        return $this->className;
    }

    public function setClassName(?ClassName $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getMatter(): ?Matter
    {
        return $this->matter;
    }

    public function setMatter(?Matter $matter): static
    {
        $this->matter = $matter;

        return $this;
    }
}
