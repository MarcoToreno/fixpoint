<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity('name', message: 'Така категорія вже існує.')]
class Category
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank(message: 'Вкажіть назву категорії.')]
    #[Assert\Length(max: 80)]
    private string $name = '';

    #[ORM\Column(length: 240)]
    #[Assert\NotBlank(message: 'Додайте короткий опис.')]
    #[Assert\Length(max: 240)]
    private string $description = '';

    /** @var Collection<int, RepairService> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: RepairService::class)]
    private Collection $services;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = trim($name); }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = trim($description); }
    public function getServices(): Collection { return $this->services; }
    public function __toString(): string { return $this->name; }
}
