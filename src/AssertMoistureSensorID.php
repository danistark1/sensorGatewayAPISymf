<?php


namespace App;



use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */
class AssertMoistureSensorID extends Constraint {
    public $value ;
    public $message = 'Sensor ID is invalid';


    // in the base Symfony\Component\Validator\Constraint class
    public function validatedBy() {
        return static::class.'Validator';
    }
    
}
