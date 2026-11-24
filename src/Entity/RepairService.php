<?php

namespace App\Entity;

use App\Repository\RepairServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RepairServiceRepository::class)]
class RepairService
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Виберіть категорію.')]
    private ?Category $category = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Вкажіть назву послуги.')]
    #[Assert\Length(max: 120)]
    private string $title = '';

    #[ORM\Column(length: 120)]
    private string $searchTitle = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Додайте опис.')]
    #[Assert\Length(max: 1600)]
    private string $description = '';

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 100000)]
    private int $price = 1;

    #[ORM\Column]
    #[Assert\Range(min: 5, max: 10080)]
    private int $duration = 30;

    #[ORM\Column]
    private bool $available = true;

    public function getId(): ?int { return $this->id; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(Category $category): void { $this->category = $category; }
    public function getTitle(): string { return $this->title; }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
        $this->searchTitle = mb_strtolower($this->title, 'UTF-8');
    }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = trim($description); }
    public function getPrice(): int { return $this->price; }
    public function setPrice(int $price): void { $this->price = $price; }
    public function getDuration(): int { return $this->duration; }
    public function setDuration(int $duration): void { $this->duration = $duration; }
    public function isAvailable(): bool { return $this->available; }
    public function setAvailable(bool $available): void { $this->available = $available; }
    public function __toString(): string { return $this->title; }
}
