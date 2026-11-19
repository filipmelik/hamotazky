<?php
declare (strict_types=1);

namespace App\Controller;

use App\DataSource\DataSourceV1;
use App\Entity\LicenceClass;
use App\Entity\Api\QuestionsForTopicResponse;
use App\Logic\IndividualTestComposer;
use App\Logic\IndividualTestEvaluator;
use App\Utils\JsonResponse;
use App\Utils\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class ApiV1Controller extends AbstractController
{

    const MAX_QUESTIONS_PER_REQUEST = 100;
    const DEFAULT_RANDOM_QUESTIONS_COUNT = 10;

    #[Route('/api/v1/topics', name: 'api-v1-get-topics', methods: ['GET', 'OPTIONS'])]
    public function getAllTopics(Request $request, DataSourceV1 $dataSource): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        return $response;
      }

      try {
        $licenceClassesFilter = Utils::prepareLicenceClassesFilter($request);
        $topics = $dataSource->getAllTopics($licenceClassesFilter);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($topics), true);
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        return $response;
      }
    }

    #[Route('/api/v1/grouped-topics', name: 'api-v1-get-grouped-topics', methods: ['GET', 'OPTIONS'])]
    public function getGroupedTopics(Request $request, DataSourceV1 $dataSource): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        return $response;
      }

      try {
        $licenceClassesFilter = Utils::prepareLicenceClassesFilter($request);
        $topics = $dataSource->getGroupedTopics($licenceClassesFilter);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($topics), true);
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        return $response;
      }
    }

    #[Route('/api/v1/questions-by-topic-id', name: 'api-v1-get-questions-by-topic-id', methods: ['GET', 'OPTIONS'])]
    public function getGetQuestionsForTopicId(Request $request, DataSourceV1 $dataSource): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        return $response;
      }

      try {
        $rawTopicId = $request->query->get('topicId');
        $topicId = $this->validateTopicId($rawTopicId);
        $licenceClassesFilter = Utils::prepareLicenceClassesFilter($request);
        $questionIds = $dataSource->getQuestionIdsFromTopic($topicId, $licenceClassesFilter);
        $questions = $dataSource->getQuestionsByIds($questionIds);
        $topic = $dataSource->getTopic($topicId);
        $topic->questionCount = count($questions); // patch count to cope with possible licence class filtering

        $licenceClasses = empty($licenceClassesFilter) ? LicenceClass::ALL : $licenceClassesFilter;

        $payload = new QuestionsForTopicResponse(
          $questions,
          $topic,
          $licenceClasses,
        );
        
        $response = JsonResponse::prepareOkJsonResponse(json_encode($payload), true);
    
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        return $response;
      }
    }

    #[Route('/api/v1/questions/{questionIds}', name: 'api-v1-get-questions-by-ids', methods: ['GET', 'OPTIONS'])]
    public function getQuestionsByIds(Request $request, string $questionIds, DataSourceV1 $dataSource): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        return $response;
      }

      try {
        $ids = $this->validateQuestionIds($questionIds);

        $missingIds = $dataSource->findMissingQuestionIds($ids);
        if (!empty($missingIds)) {
          $response = JsonResponse::prepareErrorJsonResponse(
            sprintf("Questions with following IDs were not found: [%s]", implode(', ', $missingIds)),
            Response::HTTP_NOT_FOUND,
            true,
          );
          return $response;
        }

        // return questions in the same order as they were requested
        $questionsById = [];
        foreach ($dataSource->getQuestionsByIds($ids) as $question) {
          $questionsById[$question->questionId] = $question;
        }
        $questions = [];
        foreach ($ids as $id) {
          $questions[] = $questionsById[$id];
        }

        $response = JsonResponse::prepareOkJsonResponse(json_encode($questions), true);
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        return $response;
      }
    }

    #[Route('/api/v1/random-questions', name: 'api-v1-get-random-questions', methods: ['GET', 'OPTIONS'])]
    public function getRandomQuestions(Request $request, DataSourceV1 $dataSource): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        return $response;
      }

      try {
        $licenceClassesFilter = Utils::prepareLicenceClassesFilter($request);
        $count = $this->validateQuestionCount($request->query->get('count'));

        $rawTopicSlugs = $request->query->get('topicSlug');
        if ($rawTopicSlugs === null) {
          $questionIds = $dataSource->getAllQuestionIds($licenceClassesFilter);
        } else {
          $topicIds = $this->resolveTopicSlugs($rawTopicSlugs, $dataSource);
          $questionIds = $dataSource->getQuestionIdsFromTopics($topicIds, $licenceClassesFilter);
        }

        if (empty($questionIds)) {
          $response = JsonResponse::prepareOkJsonResponse(json_encode([]), true);
          return $response;
        }

        $randomizer = new \Random\Randomizer();
        $selectedIds = array_slice($randomizer->shuffleArray($questionIds), 0, $count);
        $questions = $randomizer->shuffleArray($dataSource->getQuestionsByIds($selectedIds));

        $response = JsonResponse::prepareOkJsonResponse(json_encode($questions), true);
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        return $response;
      }
    }

    #[Route('/api/v1/get-test', name: 'api-v1-get-test', methods: ['GET', 'OPTIONS'])]
    public function getTest(Request $request, IndividualTestComposer $testComposer): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        return $response;
      }

      try {
        $rawLicenceClass = $request->query->get('licenceClass');
        $licenceClass = LicenceClass::validateLicenceClass($rawLicenceClass);
        $test = $testComposer->prepareTest($licenceClass);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($test), true);
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        return $response;
      }
    }

    #[Route('/api/v1/evaluate-test', name: 'api-v1-evaluate-test', methods: ['POST', 'OPTIONS'])]
    public function evaluateTest(Request $request, IndividualTestEvaluator $testEvaluator): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        return $response;
      }

      try {
        $payload = json_decode($request->getContent(), true);
        $answers = $payload['answers'];
        $test = $payload['test'];

        $result = $testEvaluator->evaluateTest($answers, $test);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($result), true);
        return $response;

      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        return $response;
      }
    }

    /**
     * Validate topic ID
     *
     * @param mixed $topicId
     * @return string
     */
    private function validateTopicId(mixed $topicId): string
    {
      if ($topicId === null) {
        throw new \InvalidArgumentException(
          sprintf(
            "'topicId' parameter is empty. Please provide numeric 'topicId'.", 
            implode(', ', LicenceClass::ALL),
          )
        );
      }

      if (is_numeric($topicId) === false) {
        throw new \InvalidArgumentException(
          sprintf(
            "'topicId' should be an integer value, but '{$topicId}' was supplied.", 
            implode(', ', LicenceClass::ALL),
          )
        );
      }

      return $topicId; 
    }

    /**
     * Validate comma separated list of question IDs
     *
     * @param string $rawQuestionIds
     * @return string[]
     */
    private function validateQuestionIds(string $rawQuestionIds): array
    {
      $ids = Utils::parseCommaSeparatedList($rawQuestionIds);

      if (empty($ids)) {
        throw new \InvalidArgumentException(
          "No question IDs provided. Please provide comma separated list of question IDs."
        );
      }

      if (count($ids) > self::MAX_QUESTIONS_PER_REQUEST) {
        throw new \InvalidArgumentException(
          sprintf("Too many question IDs. At most %d IDs can be requested at once.", self::MAX_QUESTIONS_PER_REQUEST)
        );
      }

      foreach ($ids as $id) {
        if (preg_match('/^[A-Za-z0-9_-]+$/', $id) !== 1) {
          throw new \InvalidArgumentException("Question ID '{$id}' is invalid.");
        }
      }

      return $ids;
    }

    /**
     * Validate requested number of questions, use default when not provided
     *
     * @param mixed $rawCount
     * @return int
     */
    private function validateQuestionCount(mixed $rawCount): int
    {
      if ($rawCount === null) {
        return self::DEFAULT_RANDOM_QUESTIONS_COUNT;
      }

      $count = filter_var($rawCount, FILTER_VALIDATE_INT);
      if ($count === false || $count < 1 || $count > self::MAX_QUESTIONS_PER_REQUEST) {
        throw new \InvalidArgumentException(
          sprintf("'count' should be an integer between 1 and %d.", self::MAX_QUESTIONS_PER_REQUEST)
        );
      }

      return $count;
    }

    /**
     * Resolve comma separated topic slugs to topic IDs, fail on unknown slugs
     *
     * @param string $rawTopicSlugs
     * @param DataSourceV1 $dataSource
     * @return string[]
     */
    private function resolveTopicSlugs(string $rawTopicSlugs, DataSourceV1 $dataSource): array
    {
      $slugs = Utils::parseCommaSeparatedList($rawTopicSlugs);
      if (empty($slugs)) {
        throw new \InvalidArgumentException(
          "'topicSlug' parameter is empty. Please provide one or more comma separated topic slugs."
        );
      }

      $topicIds = [];
      $foundSlugs = [];
      foreach ($dataSource->getTopicsBySlugs($slugs) as $topic) {
        $topicIds[] = $topic->topicId;
        $foundSlugs[] = $topic->topicSlug;
      }

      $unknownSlugs = array_diff($slugs, $foundSlugs);
      if (!empty($unknownSlugs)) {
        throw new \InvalidArgumentException(
          sprintf("Unknown topic slugs: [%s]", implode(', ', $unknownSlugs))
        );
      }

      return $topicIds;
    }

}
