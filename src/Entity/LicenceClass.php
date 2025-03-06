<?php
declare (strict_types=1);

namespace App\Entity;

class LicenceClass {

    const CLASS_A = 'A';
    const CLASS_N = 'N';

    const CLASS_NAME_A = 'HAREC';
    const CLASS_NAME_N = 'NOVICE';

    const ALL = [self::CLASS_A, self::CLASS_N];

    const CLASS_TO_CLASS_NAME = [
        self::CLASS_A => self::CLASS_NAME_A,
        self::CLASS_N => self::CLASS_NAME_N,
    ];

    /**
     * Validate input licenceClass
     *
     * @param mixed $licenceClass
     * @return string
     */
    static function validateLicenceClass(mixed $licenceClass): string
    {
      if ($licenceClass === null) {
        throw new \InvalidArgumentException(
          sprintf(
            "'licenceClass' parameter not provided. please supply one of those values: [%s]", 
            implode(', ', LicenceClass::ALL),
          )
        );
      }

      $licenceClass = strtoupper($licenceClass);
      if (!in_array($licenceClass, LicenceClass::ALL)) {
        throw new \InvalidArgumentException(
          sprintf(
            "'licenceClass' parameter is invalid. please supply one of those values: [%s]", 
            implode(', ', LicenceClass::ALL),
          )
        );
      }

      return $licenceClass;
    }

}