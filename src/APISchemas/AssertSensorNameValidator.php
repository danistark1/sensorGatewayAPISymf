<?php


namespace App\APISchemas;
use App\Entity\SensorEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class AssertSensorNameValidator
 *
 * @package App
 */
 class AssertSensorNameValidator extends ConstraintValidator {
    public function isValid($sensorName, Constraint $constraint) {
        $validSensorNames = SensorEntity::$validSensorNames;
        if (in_array($sensorName,  $validSensorNames)) {
            return true;
        } else {
            return false;
        }
    }


     /**
      * @param mixed $value
      * @param Constraint $constraint
      * @return mixed
      */
     public function validate($value, Constraint $constraint) {
         if (!$this->isValid($value, $constraint)) {
           return $this->context->buildViolation($constraint->message, [$value])->addViolation();
         }
     }
 }
