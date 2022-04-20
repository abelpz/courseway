<?php
namespace CourseWay\Validation;
use Symfony\Component\Validator\Validation;

class Validator{

  public static function validate($req, $params, $constraints){

    $validator = Validation::createValidator();
    
    $violations = $validator->validate($params, $constraints);
    
    if (0 !== count($violations)) {
      // there are errors, now you can show them
      $message = [];
      foreach ($violations as $violation) {
        $cause = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        array_push($message, $cause);
      }
      throwException($req, '400', implode(' | ', $message));
    }
  }
}