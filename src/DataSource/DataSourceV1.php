<?php
declare (strict_types=1);

namespace App\DataSource;

use App\Entity\Api\Answer;
use App\Entity\Api\Topic;
use App\Entity\Api\TopicGroup;
use App\Entity\Api\Question;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class DataSourceV1 {

    /**
     * @var Connection
     */
    private Connection $db;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Get all topics
     * 
     * @param array|null $licenceClassesFilter
     * @return Topic[]
     */
    public function getAllTopics(?array $licenceClassesFilter = null): array 
    {
        $allTopicIds = $this->getAllTopicIds();

        return $this->fetchTopicsByIds($allTopicIds, $licenceClassesFilter);
    }

    /**
     * Get all topics grouped into topic groups with optional licence classes filter 
     *
     * @param array|null $licenceClassesFilter
     * @return TopicGroup[]
     */
    public function getGroupedTopics(?array $licenceClassesFilter = null): array 
    {
        $topicGroupsData = $this->getTopicGroupsData();
        $allTopicIds = $this->getAllTopicIds();
        $allTopics = $this->fetchTopicsByIds($allTopicIds, $licenceClassesFilter);

        $groupedTopics = [];
        foreach ($allTopics as $topic) {
            $topicGroupId = $topic->topicGroupId;

            if (!isset($groupedTopics[$topicGroupId])) {
                $groupedTopics[$topicGroupId] = [];
            }
            $groupedTopics[$topicGroupId][] = $topic;
        }

        $topicGroups = [];
        foreach($groupedTopics as $topicGroupId => $topics) {
            $topicGroupName = $topicGroupsData[$topicGroupId]['name'];
            
            $topicGroups[] = new TopicGroup(
                (string)$topicGroupId,
                $topicGroupName,
                $topics,
            );
        }      

        return $topicGroups;
    }

    /**
     * Fetch Question objects for given question IDs
     *
     * @param string[] $questionIds
     * @param bool $shuffleAnswers
     */
    public function getQuestionsByIds(array $questionIds, bool $shuffleAnswers = true): array 
    {        
        $sql = '
            SELECT q.`questionId`, q.`questionCode`, q.`text`, q.`points`, q.`correctAnswerId`, t2q.`topicId`
            FROM Question q
            JOIN Topic2Question t2q ON (q.`questionId` == t2q.`questionId`)
            WHERE q.`questionId` IN (?)
            ORDER BY t2q.`topicId`, CAST(q.`questionCode` AS INTEGER) ASC
        ';
        $query = $this->db->executeQuery($sql, [$questionIds], [ArrayParameterType::STRING]);
        $dbQuestionsData = $query->fetchAllAssociative();
        $answersData = $this->fetchAnswersForQuestionIds($questionIds);

        if ($shuffleAnswers) {
            $answersData = $this->shuffleAnswers($answersData);
        }

        $questions = [];
        foreach ($dbQuestionsData as $item) {
            $questionId = $item['questionId'];
            $answers = $answersData[$questionId];

            $questions[] = new Question(
                $questionId,
                $item['questionCode'],
                $item['text'],
                $item['points'],
                $answers,
                (string)$item['topicId'],
            );
        } 

        return $questions;
    }

    /**
     * Get question IDs from selected topic, optionally limit just to selected licence clases
     *
     * @param string $topicId
     * @param array|null $licenceClassFilter
     * @return string[]
     */
    public function getQuestionIdsFromTopic(string $topicId, array $licenceClassFilter = null): array
    {
        $questionIds = [];

        $sql = '
            SELECT t2q.`questionId` 
            FROM Topic2Question t2q
            JOIN Question2LicenceClass q2l ON (q2l.`questionId` = t2q.`questionId`)
            WHERE t2q.`topicId` = :topicId
        ';
        $queryParams = ['topicId' => $topicId];
        $queryParamTypes = ['topicId' => ParameterType::STRING];

        if ($licenceClassFilter !== null) {
            $sql .= " AND q2l.`licenceClass` IN (:licenceClasses)";
            $queryParams['licenceClasses'] = $licenceClassFilter;
            $queryParamTypes['licenceClasses'] = ArrayParameterType::STRING;
        }

        $sql .= ' GROUP BY t2q.`questionId`';

        $query = $this->db->executeQuery($sql, $queryParams, $queryParamTypes);
        $data = $query->fetchAllAssociative();

        if ($data !== null) {
            foreach ($data as $row) {
                $questionIds[] = (string)$row['questionId'];
            }
        }

        return $questionIds;
    }

    /**
     * @param string $topicId
     * @return Topic
     */
    public function getTopic(string $topicId): Topic 
    {
        $dbTopicsData = $this->fetchTopicsByIds([$topicId]);
        
        return current($dbTopicsData);
    }

    /**
     * @param string $topicId
     * @return Topic
     */
    public function getTopicBySlug(string $topicSlug): Topic 
    {
        $dbTopicsData = $this->fetchTopicsBySlugs([$topicSlug]);
        
        return current($dbTopicsData);
    }

    /**
     * Get IDs of all questions present in database, optionally filtered by licence class
     *
     * @param array|null $licenceClassFilter
     * @return string[]
     */
    public function getAllQuestionIds(?array $licenceClassFilter = null): array
    {
        $questionIds = [];

        $sql = '
            SELECT q.`questionId` 
            FROM Question q
            JOIN Question2LicenceClass q2l ON (q2l.`questionId` = q.`questionId`)
        ';

        $queryParams = [];
        $queryParamTypes = [];

        if ($licenceClassFilter !== null) {
            $sql .= " AND q2l.`licenceClass` IN (:licenceClasses)";
            $queryParams['licenceClasses'] = $licenceClassFilter;
            $queryParamTypes['licenceClasses'] = ArrayParameterType::STRING;
        }

        $sql .= ' GROUP BY q.`questionId`';

        $query = $this->db->executeQuery($sql, $queryParams, $queryParamTypes);
        $data = $query->fetchAllAssociative();

        if ($data !== null) {
            foreach ($data as $row) {
                $questionIds[] = (string)$row['questionId'];
            }
        }

        return $questionIds;
    }

    /**
     * Randomly shuffle provided answers associative array 
     *
     * @param array $answersData
     * @return array Shuffled answers
     */
    private function shuffleAnswers(array $answersData): array
    {
        $shuffled = [];
        foreach ($answersData as $questionId => $answers) {
            shuffle($answers);
            $shuffled[$questionId] = $answers;
        }

        return $shuffled;
    }

    /**
     * Fetch array of Answer objects grouped by question ID for given question IDs
     *
     * @param string[] $questionIds
     */
    private function fetchAnswersForQuestionIds(array $questionIds): array 
    {        
        $sql = '
            SELECT `questionId`, `correctAnswerId`
            FROM Question
            WHERE `questionId` IN (?)
        ';
        $query = $this->db->executeQuery($sql, [$questionIds], [ArrayParameterType::STRING]);
        $correctAnswerIdsData = $query->fetchAllKeyValue();

        $sql = '
            SELECT `answerId`, `questionId`, `text`
            FROM Answer
            WHERE `questionId` IN (?)
            ORDER BY `defaultOrder` ASC
        ';
        $query = $this->db->executeQuery($sql, [$questionIds], [ArrayParameterType::STRING]);
        $dbAnswersData = $query->fetchAllAssociative();

        $groupedAnswers = [];
        foreach ($dbAnswersData as $item) {
            $questionId = $item['questionId'];

            if (!isset($groupedAnswers[$questionId])) {
                $groupedAnswers[$questionId] = [];
            }
            $isCorrectAnswer = (string)$correctAnswerIdsData[$questionId] === (string)$item['answerId'];

            $groupedAnswers[$questionId][] = new Answer(
                (string)$item['answerId'],
                $item['text'],
                $isCorrectAnswer,
            );
        } 

        return $groupedAnswers;
    }

    /**
     * Fetch Topic objects for given topic IDs
     *
     * @param int[] $topicIds
     * @param array|null $licenceClassesFilter
     * @return Topic[]
     */
    private function fetchTopicsByIds(array $topicIds, ?array $licenceClassesFilter = null): array 
    {        
        $sql = '
            SELECT `topicId`, `shortName`, `slug`, `topicGroupId`
            FROM Topic
            WHERE `topicId` IN (?)
            ORDER BY `order` ASC
        ';
        $query = $this->db->executeQuery($sql, [$topicIds], [ArrayParameterType::INTEGER]);
        $dbTopicsData = $query->fetchAllAssociative();

        $topics = [];
        foreach ($dbTopicsData as $item) {
            $topicId = (string)$item['topicId'];
            $topicQuestionIds = $this->getQuestionIdsFromTopic($topicId, $licenceClassesFilter);
            $topics[] = new Topic(
                $topicId, $item['shortName'], $item['slug'], count($topicQuestionIds), (string)$item['topicGroupId']
            );
        } 

        return $topics;
    }

    /**
     * Fetch Topic objects for given topic slugs
     *
     * @param int[] $topicSlugs
     * @param array|null $licenceClassesFilter
     * @return Topic[]
     */
    private function fetchTopicsBySlugs(array $topicSlugs, ?array $licenceClassesFilter = null): array 
    {        
        $sql = '
            SELECT `topicId`, `shortName`, `slug`, `topicGroupId`
            FROM Topic
            WHERE `slug` IN (?)
            ORDER BY `order` ASC
        ';
        $query = $this->db->executeQuery($sql, [$topicSlugs], [ArrayParameterType::STRING]);
        $dbTopicsData = $query->fetchAllAssociative();

        $topics = [];
        foreach ($dbTopicsData as $item) {
            $topicId = (string)$item['topicId'];
            $topicQuestionIds = $this->getQuestionIdsFromTopic($topicId, $licenceClassesFilter);
            $topics[] = new Topic(
                $topicId, $item['shortName'], $item['slug'], count($topicQuestionIds), (string)$item['topicGroupId']
            );
        } 

        return $topics;
    }

    /**
     * Get IDs of all topics present in database
     *
     * @return string[]
     */
    private function getAllTopicIds(): array
    {
        $sql = '
            SELECT `topicId`
            FROM Topic
            ORDER BY `order` ASC
        ';

        $stringIds = [];
        foreach ($this->db->fetchFirstColumn($sql) as $intTopicId) {
            $stringIds[] = (string)$intTopicId;
        }

        return $stringIds;
    }

    /**
     * Get all topic groups present in database
     *
     * @return array[]
     */
    private function getTopicGroupsData(): array
    {
        $sql = '
            SELECT `topicGroupId`, `name`
            FROM TopicGroup
            ORDER BY `order` ASC
        ';

        $query = $this->db->executeQuery($sql);
        $dbTopicGroupsData = $query->fetchAllAssociative();

        $topicGroupsData = [];
        foreach ($dbTopicGroupsData as $item) {
            $topicGroupsData[(string)$item['topicGroupId']] = [
                'name' => $item['name']
            ];
        }

        return $topicGroupsData;
    }

}