<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class Rut extends Constraint
{
    public string $message = 'El RUT "{{ value }}" no es válido.';
}