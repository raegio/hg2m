<?php

namespace App\Model\Backend;

class BookingFilter
{
    private $year;
    private $status = 0;
    private $search;

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): self
    {
        $this->search = $search;

        return $this;
    }

    public function fromArray(array $parameters)
    {
        $this->year = $parameters['year'] ?? null;
        $this->status = $parameters['status'];
        $this->search = $parameters['search'] ?? null;
    }

    public function toArray()
    {
        return [
            'year' => $this->year,
            'status' => $this->status,
            'search' => $this->search,
        ];
    }
}
