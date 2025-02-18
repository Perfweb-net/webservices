<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use App\Entity\Question;

class AtLeastOneCorrectAnswerValidator extends ConstraintValidator
{
  public function validate(mixed $value, Constraint $constraint): void
  {
    if (!$constraint instanceof AtLeastOneCorrectAnswer) {
      throw new UnexpectedTypeException($constraint, AtLeastOneCorrectAnswer::class);
    }

    if (!$value instanceof Question) {
      throw new UnexpectedValueException($value, Question::class);
    }

    $hasCorrectAnswer = false;

    foreach ($value->getAnswers() as $answer) {
      if ($answer->isCorrect()) {
        $hasCorrectAnswer = true;
        break;
      }
    }

    if (!$hasCorrectAnswer) {
      $this->context->buildViolation($constraint->message)
        ->atPath('answers')
        ->addViolation();
    }
  }
}
