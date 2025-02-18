<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255, nullable: false)]
  #[Assert\NotBlank(message: 'The title is required')]
  #[Assert\Length(
    max: 255,
    maxMessage: 'The title must be less than {{ limit }} characters'
  )]
  private ?string $title = null;

  #[ORM\Column(type: Types::TEXT, nullable: false)]
  #[Assert\NotBlank(message: 'The description is required')]
  #[Assert\Length(
    max: 5000,
    maxMessage: 'The description must be less than {{ limit }} characters'
  )]
  private ?string $description = null;

  #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'quiz', orphanRemoval: true)]
  private Collection $questions;

  #[ORM\ManyToOne(inversedBy: 'quizzes')]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $owner = null;

  public function __construct()
  {
      $this->questions = new ArrayCollection();
  }

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

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(string $description): static
  {
    $this->description = $description;

    return $this;
  }

  /**
   * @return Collection<int, Question>
   */
  public function getQuestions(): Collection
  {
      return $this->questions;
  }

  public function addQuestion(Question $question): static
  {
      if (!$this->questions->contains($question)) {
          $this->questions->add($question);
          $question->setQuiz($this);
      }

      return $this;
  }

  public function removeQuestion(Question $question): static
  {
      if ($this->questions->removeElement($question)) {
          // set the owning side to null (unless already changed)
          if ($question->getQuiz() === $this) {
              $question->setQuiz(null);
          }
      }

      return $this;
  }

  public function getOwner(): ?User
  {
      return $this->owner;
  }

  public function setOwner(?User $owner): static
  {
      $this->owner = $owner;

      return $this;
  }
}
