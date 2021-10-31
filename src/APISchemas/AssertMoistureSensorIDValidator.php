<?php


namespace App\APISchemas;
use App\Entity\SensorMoistureEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class AssertSensorIDValidator
 *
 * @package App
 */
 class AssertMoistureSensorIDValidator extends ConstraintValidator {
    public function isValid($sensorId, Constraint $constraint) {
        $validSensorIds = SensorMoistureEntity::getValidSensorIDs();
        if (in_array($sensorId,  $validSensorIds)) {
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
