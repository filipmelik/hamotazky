<?php
declare (strict_types=1);

namespace App\Logic;

use App\DataSource\DataSourceV1;
use App\Entity\TestDefinition;
use App\Entity\Api\Test;
use App\Entity\Api\TestPart;

class IndividualTestComposer {


    private DataSourceV1 $dataSource;

    /**
     * @param DataSourceV1 $dataSource
     */
    public function __construct(DataSourceV1 $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * Prepare new test for selected licence class
     *
     * @param string $selectedLicenceClass
     * @return Test Test data
     */
    public function prepareTest(string $selectedLicenceClass): Test
    {
        $groupedTopics = $this->dataSource->getGroupedTopics([$selectedLicenceClass]);
        $testDefinition = TestDefinition::LICENCE_CLASS_TO_TEST_DEFINITION[$selectedLicenceClass];

        $testParts = [];
        foreach ($groupedTopics as $topicGroup) {
            $topicGroupId = $topicGroup->topicGroupId;
            
            $topicGroupQuestions = [];
            foreach ($testDefinition[$topicGroupId] as $topicGroupData) {
                $topicSet = $topicGroupData['topicSet'];
                $questionCount = $topicGroupData['questionCount'];

                $randomQuestionsFromTopicSet = $this->getRandomQuestionsFromTopicSet(
                    $topicSet, 
                    $questionCount, 
                    $selectedLicenceClass
                );
                $topicGroupQuestions = array_merge($topicGroupQuestions, $randomQuestionsFromTopicSet);
            }

            $testParts[] = new TestPart(
                $topicGroup,
                $topicGroupQuestions,
            );
        }

        $test = new Test(
            $testParts,
            $selectedLicenceClass,
        );
        
        return $test;
    }

    /**
     * @param string[] $topicSet
     * @param integer $questionCount
     * @param string $selectedLicenceClass
     * @return Question[]
     */
    private function getRandomQuestionsFromTopicSet(
        array $topicSet, 
        int $questionCount, 
        string $selectedLicenceClass
    ): array
    {
        $questionsFromTopicsInTopicSet = $this->getAllQuestionsFromTopicsInTopicSet($topicSet, $selectedLicenceClass);
        $selectedQuestionsKeys = array_rand($questionsFromTopicsInTopicSet, $questionCount);

        $randomQuestionsFromTopicSet = [];
        foreach ((array)$selectedQuestionsKeys as $key) {
            $randomQuestionsFromTopicSet[] = $questionsFromTopicsInTopicSet[$key];
        }

        return $randomQuestionsFromTopicSet;
    }

    /**
     * @param string[] $topicSet
     * @param string $selectedLicenceClass
     * @return Question[]
     */
    private function getAllQuestionsFromTopicsInTopicSet(array $topicSet, string $selectedLicenceClass): array
    {
        $questions = [];
        foreach ($topicSet as $topicId) {
            $questionIdsFromTopic = $this->dataSource->getQuestionIdsFromTopic($topicId, [$selectedLicenceClass]);
            $questionsFromTopic = $this->dataSource->getQuestionsByIds($questionIdsFromTopic);
            $questions = array_merge($questions, $questionsFromTopic);
        }

        return $questions;
    }

}