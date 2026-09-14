<?php
namespace App\Validator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RutValidator extends ConstraintValidator {
  public function validate($value, Constraint $constraint){
    if(!$value) return;
    if(!RutHelper::isValid($value)){
        $this->context->buildViolation('RUT inválido: formato 12.345.678-9, DV incorrecto')->addViolation();
    }
  }
}
