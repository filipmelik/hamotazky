<?php
declare (strict_types=1);

namespace App\Logic;

use App\Entity\Api\TestEvaluationResult;
use App\Entity\Api\TestEvaluation;
use App\Entity\Api\TestEvaluationTopicGroupResult;
use App\Entity\LicenceClass;

class IndividualTestEvaluator {

    const HAREC_TOPIC_GROUP_ID_2_REQUESTED_CORRECT_ANSWER_COUNTS = [
        '1' => 16,
        '2' => 32,
        '3' => 16,
    ];

    const NOVICE_TOPIC_GROUP_ID_2_REQUESTED_CORRECT_ANSWER_COUNTS = [
        '1' => 14,
        '2' => 28,
        '3' => 14,
    ];

    public function evaluateTest(
        array $answers,
        array $test, 
    ): TestEvaluationResult {
        $licenceClass = $test['licenceClass'];

        match ($licenceClass) {
            LicenceClass::CLASS_A => $evaluationResult = $this->evaluateHarecTest($answers, $test),
            LicenceClass::CLASS_N => $evaluationResult = $this->evaluateNoviceTest($answers, $test),
            default => throw new \InvalidArgumentException('Unknown licence class supplied: ' . $licenceClass),
        };
        
        return new TestEvaluationResult(
            $licenceClass,
            $answers,
            $test,
            $evaluationResult,
        );
    }

    /**
     * Evaluate HAREC class individual test
     *
     * @param array $answers
     * @param array $test
     * @return TestEvaluation Test evaluation properties
     */
    private function evaluateHarecTest(
        array $answers,
        array $test,
    ): TestEvaluation {
        $result = $this->getTestResult(
            $answers, 
            $test,
            self::HAREC_TOPIC_GROUP_ID_2_REQUESTED_CORRECT_ANSWER_COUNTS,
        );
        
        return $result;
    }

    /**
     * Evaluate NOVICE class individual test
     *
     * @param array $answers
     * @param array $test
     * @return TestEvaluation Test evaluation properties
     */
    private function evaluateNoviceTest(
        array $answers,
        array $test,
    ): TestEvaluation {
        $result = $this->getTestResult(
            $answers, 
            $test,
            self::NOVICE_TOPIC_GROUP_ID_2_REQUESTED_CORRECT_ANSWER_COUNTS,
        );
        
        return $result;
    }

    /**
     * Get data that are used as a basis for evaluating the individual test 
     *
     * @param array $answers
     * @param array $test
     * @param array $evaluationCriteria
     * @return TestEvaluation
     */
    private function getTestResult(
        array $answers,
        array $test,
        array $evaluationCriteria,
    ): TestEvaluation {
        $totalCorrectAnswers = 0;
        $totalWrongAnswers = 0;
        $resultsByTopicGroup = [];

        foreach ($test['testParts'] as $groupData) {
            $groupId = $groupData['topicGroup']['topicGroupId'];
            $temp = [
                'correctAnswersCount' => 0,
                'wrongAnswersCount'   => 0,
                'groupQuestionCount'  => count($groupData['topicGroupQuestions']),
                'minimumPointsToPass' => $evaluationCriteria[$groupId],
            ];

            foreach ($groupData['topicGroupQuestions'] as $q) {
                $questionId = $q['questionId'];
                $userAnswerId = $answers[$questionId] ?? null;

                if ($userAnswerId === null) {
                    $temp['wrongAnswersCount'] += 1;
                    $totalWrongAnswers += 1;
                    continue;
                }

                foreach ($q['answers'] as $a) {
                    if ($a['answerId'] === $userAnswerId) {
                        $isCorrectAnswer = $a['isCorrect'];
                        if ($isCorrectAnswer === true) {
                            $temp['correctAnswersCount'] += 1;
                            $totalCorrectAnswers += 1;
                        } else {
                            $temp['wrongAnswersCount'] += 1;
                            $totalWrongAnswers += 1;
                        }
                        break;
                    }
                }
            }

            $temp['passed'] = $temp['correctAnswersCount'] >= $temp['minimumPointsToPass'];

            $temp['correctAnswersPercentage'] = round(
                $temp['correctAnswersCount'] / $temp['groupQuestionCount'] * 100
            );

            $resultsByTopicGroup[$groupId] = new TestEvaluationTopicGroupResult(
                $temp['correctAnswersCount'],
                $temp['wrongAnswersCount'],
                $temp['groupQuestionCount'],
                $temp['minimumPointsToPass'],
                $temp['passed'],
                $temp['correctAnswersPercentage'],
            );
        }

        $passedOverall = true;
        foreach ($resultsByTopicGroup as $groupResult) {
            if ($groupResult->passed === false) {
                $passedOverall = false;
                break;
            }
        }
        
        return new TestEvaluation(
            $resultsByTopicGroup,
            $totalCorrectAnswers,
            $totalWrongAnswers,
            $passedOverall,
        );
    }
}