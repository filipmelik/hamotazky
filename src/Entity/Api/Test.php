<?php
declare (strict_types=1);

namespace App\Entity\Api;

class Test {

    public array $testParts;
    public string $licenceClass;

    /**
     * @param TestPart[] $testParts
     * @param string $licenceClass
     */
    public function __construct(
        array $testParts,
        string $licenceClass,
    ) {
        $this->testParts = $testParts;
        $this->licenceClass = $licenceClass;
    }

}