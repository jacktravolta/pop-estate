<?php

namespace App\Validator;

use App\Service\RutService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RutValidator extends ConstraintValidator
{
    public function __construct(private RutService $rutService) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!$this->rutService->validate((string) $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}