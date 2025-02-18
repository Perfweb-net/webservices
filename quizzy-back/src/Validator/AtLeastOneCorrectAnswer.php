<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class AtLeastOneCorrectAnswer extends Constraint
{
  #[HasNamedArguments]
  public function __construct(
    public string $message = 'The question must have at least one correct answer',
    ?array $groups = null,
    mixed $payload = null,
  ) {
    parent::__construct([], $groups, $payload);
  }
}