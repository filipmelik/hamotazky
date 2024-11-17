<?php
declare (strict_types=1);

namespace App\Entity\Api;

class TestEvaluationTopicGroupResult {

    public int $correctAnswersCount;
    public int $wrongAnswersCount;
    public int $groupQuestionCount;
    public int $minimumPointsToPass;
    public bool $passed;
    public float $correctAnswersPercentage;

    /**
     * @param integer $correctAnswersCount
     * @param integer $wrongAnswersCount
     * @param integer $groupQuestionCount
     * @param integer $minimumPointsToPass
     * @param boolean $passed
     * @param float $correctAnswersPercentage
     */
    public function __construct(
        int $correctAnswersCount,
        int $wrongAnswersCount,
        int $groupQuestionCount,
        int $minimumPointsToPass,
        bool $passed,
        float $correctAnswersPercentage,
    ) {
      $this->correctAnswersCount = $correctAnswersCount;
      $this->wrongAnswersCount = $wrongAnswersCount;
      $this->groupQuestionCount = $groupQuestionCount;  
      $this->minimumPointsToPass = $minimumPointsToPass;
      $this->passed = $passed;
      $this->correctAnswersPercentage = $correctAnswersPercentage;
    }

}