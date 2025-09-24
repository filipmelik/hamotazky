<?php
declare (strict_types=1);

namespace App\Utils;

use App\Entity\LicenceClass;
use Symfony\Component\HttpFoundation\Request;

class Utils {

  /**
     * Validate and prepare licenceClass filter array
     *
     * @param Request $request
     * @return array|null
     */
    static function prepareLicenceClassesFilter(Request $request): ?array
    {
      $licenceClassFilter = $request->query->get('licenceClass');
      if ($licenceClassFilter === null) {
        return null;
      }

      $values = explode(',', $licenceClassFilter);
      $normalizedLicenceClasses = [];
      foreach ($values as $v) {
        $normalized = strtoupper($v);
        if (!in_array($normalized, LicenceClass::ALL)) {
          throw new \InvalidArgumentException(
            sprintf(
              "'licenceClass' parameter is invalid. allowed values are only: [%s]", 
              implode(', ', LicenceClass::ALL),
            )
          );
        }
        $normalizedLicenceClasses[] = $normalized;
        $normalizedLicenceClasses = array_unique($normalizedLicenceClasses);
      }

      return $normalizedLicenceClasses;
    }

}