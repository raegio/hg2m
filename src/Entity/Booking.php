<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use App\Validator as AppAssert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[AppAssert\BookingClass]
#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    public const PLANNED = 1;
    public const IN_PROGRESS = 2;
    public const COMPLETED = 3;

    public static $STATUSES = [
        'PLANNED' => self::PLANNED,
        'IN_PROGRESS' => self::IN_PROGRESS,
        'COMPLETED' => self::COMPLETED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[Assert\NotBlank]
    private ?Gite $gite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getGite(): ?Gite
    {
        return $this->gite;
    }

    public function setGite(?Gite $gite): self
    {
        $this->gite = $gite;

        return $this;
    }
}
