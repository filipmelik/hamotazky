<?php
declare (strict_types=1);

namespace App\Entity\Api;

class TestEvaluationResult {

    public string $licenceClass;
    public array $answers;
    public array $test;
    public TestEvaluation $result;

    /**
     * @param string $licenceClass
     * @param array $answers
     * @param array $test
     * @param TestEvaluation $result
     */
    public function __construct(
        string $licenceClass,
        array $answers,
        array $test,
        TestEvaluation $result,
    ) {
      $this->licenceClass = $licenceClass;
      $this->answers = $answers;
      $this->test = $test;  
      $this->result = $result;
    }

}