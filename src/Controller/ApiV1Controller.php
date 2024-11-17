<?php
declare (strict_types=1);

namespace App\Controller;

use App\DataSource\DataSourceV1;
use App\Entity\LicenceClass;
use App\Entity\Api\QuestionsForTopicResponse;
use App\Logic\IndividualTestComposer;
use App\Logic\IndividualTestEvaluator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class ApiV1Controller extends AbstractController
{

    const JSON_CONTENT_TYPE = 'application/json';


    #[Route('/api/v1/topics', name: 'api-v1-get-topics')]
    public function getAllTopics(Request $request, DataSourceV1 $dataSource): Response
    {
      try {
        $licenceClassesFilter = $this->prepareLicenceClassesFilter($request);
        $topics = $dataSource->getAllTopics($licenceClassesFilter);
        $response = $this->prepareOkJsonResponse(json_encode($topics));
        $response->send();
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = $this->prepareErrorJsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
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
        $response = $this->prepareOkJsonResponse(json_encode($topics));
        $response->send();
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = $this->prepareErrorJsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
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
        
        $response = $this->prepareOkJsonResponse(json_encode($payload));
        $response->send();
    
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = $this->prepareErrorJsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        $response->send();
        return $response;
      }
    }

    #[Route('/api/v1/get-test', name: 'api-v1-get-test')]
    public function getTest(Request $request, IndividualTestComposer $testComposer): Response
    {
      try {
        $rawLicenceClass = $request->query->get('licenceClass');
        $licenceClass = $this->validateLicenceClass($rawLicenceClass);
        $test = $testComposer->prepareTest($licenceClass);
        $response = $this->prepareOkJsonResponse(json_encode($test));
        $response->send();
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = $this->prepareErrorJsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        $response->send();
        return $response;
      }
    }

    #[Route('/api/v1/evaluate-test', name: 'api-v1-evaluate-test', methods: ['POST', 'OPTIONS'])]
    public function evaluateTest(Request $request, IndividualTestEvaluator $testEvaluator): Response
    {
      if ($request->getMethod() === 'OPTIONS') {
        // handle CORS preflight
        $response = $this->prepareOkJsonResponse(null);
        $response->send();
        return $response;
      }

      try {
        $payload = json_decode($request->getContent(), true);
        $answers = $payload['answers'];
        $test = $payload['test'];

        $result = $testEvaluator->evaluateTest($answers, $test);
        $response = $this->prepareOkJsonResponse(json_encode($result));
        $response->send();
        return $response;

      } catch (\InvalidArgumentException $e) {
        $response = $this->prepareErrorJsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        $response->send();
        return $response;
      }
    }

    /**
     * @param string $data
     * @return Response
     */
    private function prepareOkJsonResponse(?string $jsonData): Response {
      $response = new Response();
      $response = $this->setCorsHeaders($response);
      $response->headers->set('Content-Type', self::JSON_CONTENT_TYPE);
      $response->setStatusCode(Response::HTTP_OK);
      if ($jsonData !== null) {
        $response->setContent($jsonData);
      }

      return $response;
    }

    /**
     * @param string $message
     * @param integer $statusCode
     * @return Response
     */
    private function prepareErrorJsonResponse(string $message, int $statusCode): Response {
      $response = new Response();
      $response = $this->setCorsHeaders($response);
      $response->headers->set('Content-Type', self::JSON_CONTENT_TYPE);
      $response->setContent(
        json_encode([
          'error'  => $message,
          'status' => $statusCode,
        ])
      );
      $response->setStatusCode($statusCode);

      return $response;
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

    /**
     * Validate input licenceClass
     *
     * @param mixed $licenceClass
     * @return string
     */
    private function validateLicenceClass(mixed $licenceClass): string
    {
      if ($licenceClass === null) {
        throw new \InvalidArgumentException(
          sprintf(
            "'licenceClass' parameter not provided. please supply one of those values: [%s]", 
            implode(', ', LicenceClass::ALL),
          )
        );
      }

      $licenceClass = strtoupper($licenceClass);
      if (!in_array($licenceClass, LicenceClass::ALL)) {
        throw new \InvalidArgumentException(
          sprintf(
            "'licenceClass' parameter is invalid. please supply one of those values: [%s]", 
            implode(', ', LicenceClass::ALL),
          )
        );
      }

      return $licenceClass;
    }

    /**
     * Set CORS headers to request repsonse so the API endpoints can be called out of its original domain
     *
     * @param Response $response
     * @return Response modified Response
     */
    private function setCorsHeaders(Response $response): Response
    {
      $response->headers->set('Access-Control-Allow-Origin', '*');
      $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
      $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

      return $response;
    }

}