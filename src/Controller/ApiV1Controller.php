<?php
declare (strict_types=1);

namespace App\Controller;

use App\DataSource\DataSourceV1;
use App\Entity\LicenceClass;
use App\Entity\Api\QuestionsForTopicResponse;
use App\Logic\IndividualTestComposer;
use App\Logic\IndividualTestEvaluator;
use App\Utils\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class ApiV1Controller extends AbstractController
{

    #[Route('/api/v1/topics', name: 'api-v1-get-topics')]
    public function getAllTopics(Request $request, DataSourceV1 $dataSource): Response
    {
      try {
        $licenceClassesFilter = $this->prepareLicenceClassesFilter($request);
        $topics = $dataSource->getAllTopics($licenceClassesFilter);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($topics), true);
        $response->send();
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        $response->send();
        return $response;
      }
    }

    #[Route('/api/v1/grouped-topics', name: 'api-v1-get-grouped-topics')]
    public function getGroupedTopics(Request $request, DataSourceV1 $dataSource): Response
    {
      try {
        $licenceClassesFilter = $this->prepareLicenceClassesFilter($request);
        $topics = $dataSource->getGroupedTopics($licenceClassesFilter);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($topics), true);
        $response->send();
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        $response->send();
        return $response;
      }
    }

    #[Route('/api/v1/questions-by-topic-id', name: 'api-v1-get-questions-by-topic-id')]
    public function getGetQuestionsForTopicId(Request $request, DataSourceV1 $dataSource): Response
    {
      try {
        $rawTopicId = $request->query->get('topicId');
        $topicId = $this->validateTopicId($rawTopicId);
        $licenceClassesFilter = $this->prepareLicenceClassesFilter($request);
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
        $response->send();
    
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        $response->send();
        return $response;
      }
    }

    #[Route('/api/v1/get-test', name: 'api-v1-get-test')]
    public function getTest(Request $request, IndividualTestComposer $testComposer): Response
    {
      try {
        $rawLicenceClass = $request->query->get('licenceClass');
        $licenceClass = LicenceClass::validateLicenceClass($rawLicenceClass);
        $test = $testComposer->prepareTest($licenceClass);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($test), true);
        $response->send();
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        $response->send();
        return $response;
      }
    }

    #[Route('/api/v1/evaluate-test', name: 'api-v1-evaluate-test', methods: ['POST', 'OPTIONS'])]
    public function evaluateTest(Request $request, IndividualTestEvaluator $testEvaluator): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = JsonResponse::prepareOkJsonResponse(null, true);
        $response->send();
        return $response;
      }

      try {
        $payload = json_decode($request->getContent(), true);
        $answers = $payload['answers'];
        $test = $payload['test'];

        $result = $testEvaluator->evaluateTest($answers, $test);
        $response = JsonResponse::prepareOkJsonResponse(json_encode($result), true);
        $response->send();
        return $response;

      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, true
        );
        $response->send();
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
     * Validate and prepare licenceClass filter array
     *
     * @param Request $request
     * @return array|null
     */
    private function prepareLicenceClassesFilter(Request $request): ?array
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