<?php

namespace App\Entity;

use App\Enum\ExecutionStatus;
use App\Repository\ExecutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExecutionRepository::class)]
class Execution
{
    //#region Propriétés
    /**
     * Propriété id
     * 
     * Identifiant unique de 
     * l'exécution
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var int|null $id Identifiant unique
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(name: 'id', type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    /**
     * Propriété participants
     * 
     * Liste des participants
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var Collection<int, User> $participants Liste des participants
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'executions')]
    #[ORM\JoinTable(name: 'execution_participants')]
    #[ORM\JoinColumn(name: 'execution_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_uid', referencedColumnName: 'uid')]
    private Collection $participants;

    /**
     * Propriété quiz
     * 
     * Quiz associé à l'exécution
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var Quiz|null $quiz Quiz associé
     */
    #[ORM\ManyToOne(inversedBy: 'executions')]
    private ?Quiz $quiz = null;

    /**
     * Propriété question
     * 
     * Question associée à l'exécution
     * (question courante)
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var Question|null $question Question associée
     */
    #[ORM\ManyToOne(inversedBy: 'executions')]
    private ?Question $question = null;

    /**
     * Propriété status
     * 
     * Status de l'exécution
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var ExecutionStatus|null $status Status de l'exécution
     */
    #[ORM\Column(
        length: 20,
        type: Types::STRING,
        enumType: ExecutionStatus::class,
    )]
    #[Assert\Type(
        type: ExecutionStatus::class,
        message: 'The status must be of type {{ type }}.',
    )]
    private ?ExecutionStatus $status = ExecutionStatus::WAITING;
    //#endregion

    //#region Méthodes
    /**
     * Méthode __construct
     * 
     * Constructeur de la classe
     * 
     * @access public
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }
    //#endregion

    //#region Méthodes
    /**
     * Méthode getId
     * 
     * Récupère l'identifiant unique
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return Uuid|null Identifiant unique
     */
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    /**
     * Méthode getParticipants
     * 
     * Récupère la liste des participants
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return Collection<int, User> Liste des participants
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * Méthode addParticipant
     * 
     * Permet d'ajouter un participant
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param User $participant Participant à ajouter
     * 
     * @return static Instance courante
     */
    public function addParticipant(User $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    /**
     * Méthode removeParticipant
     * 
     * Permet de retirer un participant
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param User $participant Participant à retirer
     * 
     * @return static Instance courante
     */
    public function removeParticipant(User $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * Méthode getQuiz
     * 
     * Récupère le quiz associé
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return Quiz Quiz associé
     */
    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    /**
     * Méthode setQuiz
     * 
     * Définit le quiz associé
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Quiz|null $quiz Quiz à associer
     * 
     * @return static Instance courante
     */
    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }

    /**
     * Méthode getQuestion
     * 
     * Récupère la question associée
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return Question|null Question associée
     */
    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    /**
     * Méthode setQuestion
     * 
     * Définit la question associée
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param Question|null $question Question à associer
     * 
     * @return static Instance courante
     */
    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Méthode getStatus
     * 
     * Récupère le status de l'exécution
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return ExecutionStatus|null Status de l'exécution
     */
    public function getStatus(): ?ExecutionStatus
    {
        return $this->status;
    }

    /**
     * Méthode setStatus
     * 
     * Définit le status de l'exécution
     * 
     * @access public
     * @since 1.0.0
     * 
     * @param ExecutionStatus $status Status à définir
     * 
     * @return static Instance courante
     */
    public function setStatus(ExecutionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Méthode isWaiting
     * 
     * Vérifie si l'exécution est en attente
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return bool Vrai si l'exécution est en attente, faux sinon
     */
    public function isWaiting(): bool
    {
        return $this->status === ExecutionStatus::WAITING;
    }

    /**
     * Méthode isStarted
     * 
     * Vérifie si l'exécution a démarré
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return bool Vrai si l'exécution a démarré, faux sinon
     */
    public function isStarted(): bool
    {
        return $this->status === ExecutionStatus::STARTED;
    }

    /**
     * Méthode isEnded
     * 
     * Vérifie si l'exécution est terminée
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return bool Vrai si l'exécution est terminée, faux sinon
     */
    public function isEnded(): bool
    {
        return $this->status === ExecutionStatus::ENDED;
    }
    //#endregion
}
