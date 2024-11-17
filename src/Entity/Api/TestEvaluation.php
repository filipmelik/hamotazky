<?php
declare (strict_types=1);

namespace App\Entity\Api;

class TestEvaluation {

    public array $resultsByTopicGroup;
    public int $totalCorrectAnswers;
    public int $totalWrongAnswers;
    public bool $passedOverall;

    /**
     * @param array $resultsByTopicGroup
     * @param int $totalCorrectAnswers
     * @param int $totalWrongAnswers
     * @param bool $passedOverall
     */
    public function __construct(
        array $resultsByTopicGroup,
        int $totalCorrectAnswers,
        int $totalWrongAnswers,
        bool $passedOverall,
    ) {
      $this->resultsByTopicGroup = $resultsByTopicGroup;
      $this->totalCorrectAnswers = $totalCorrectAnswers;
      $this->totalWrongAnswers = $totalWrongAnswers;  
      $this->passedOverall = $passedOverall;
    }

}