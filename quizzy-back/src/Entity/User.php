<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\Column(length: 28)]
    private ?string $Uuid = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    public function getUuid(): ?string
    {
        return $this->Uuid;
    }

    public function setUuid(string $Uuid): static
    {
        $this->Uuid = $Uuid;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }
}
