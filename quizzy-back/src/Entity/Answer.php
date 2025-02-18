<?php

namespace App\Entity;

use App\Repository\AnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AnswerRepository::class)]
class Answer
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  #[Groups(['answer:read'])]
  private ?int $id = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'The title is required')]
  #[Assert\Length(max: 255, maxMessage: 'The title must be less than {{ limit }} characters')]
  private ?string $title = null;

  #[ORM\Column(type: Types::BOOLEAN)]
  #[Groups(['answer:read'])]
  private ?bool $isCorrect = null;

  #[ORM\ManyToOne(inversedBy: 'answers')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Question $question = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getTitle(): ?string
  {
    return $this->title;
  }

  public function setTitle(string $title): static
  {
    $this->title = $title;

    return $this;
  }

  public function isCorrect(): ?bool
  {
    return $this->isCorrect;
  }

  public function setIsCorrect(bool $isCorrect): static
  {
    $this->isCorrect = $isCorrect;

    return $this;
  }

  public function getQuestion(): ?Question
  {
    return $this->question;
  }

  public function setQuestion(?Question $question): static
  {
    $this->question = $question;

    return $this;
  }
}
