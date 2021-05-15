<?php


namespace App;



use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */
class AssertSensorName extends Constraint {
    public $value ;
    public $message = 'Sensor name  is invalid';


    // in the base Symfony\Component\Validator\Constraint class
    public function validatedBy() {
        return static::class.'Validator';
    }
    
}
