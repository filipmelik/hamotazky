<?php
declare (strict_types=1);

namespace App\Controller;

use App\DataSource\DataSourceV1;
use App\Entity\LicenceClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class QuestionListingController extends AbstractController
{

    #[Route('/harec/prohlizeni', name: 'harec-listing-topic-list')]
    public function harecListingTopicList(DataSourceV1 $dataSource): Response
    {
      return $this->renderTopicListForLicenceClass($dataSource, LicenceClass::CLASS_A);
    }

    #[Route('/harec/prohlizeni/{topicSlug}', name: 'harec-listing-topic-questions')]
    public function harecQuestionListingIndex(string $topicSlug, DataSourceV1 $dataSource): Response
    {
      return $this->renderQuestionListForTopicSlug($dataSource, LicenceClass::CLASS_A, $topicSlug);
    }
    
    #[Route('/novice/prohlizeni', name: 'novice-listing-topic-list')]
    public function noviceListingTopicList(DataSourceV1 $dataSource): Response
    {      
      return $this->renderTopicListForLicenceClass($dataSource, LicenceClass::CLASS_N);
    }

    #[Route('/novice/prohlizeni/{topicSlug}', name: 'novice-listing-topic-questions')]
    public function noviceQuestionListingIndex(string $topicSlug, DataSourceV1 $dataSource): Response
    {
      return $this->renderQuestionListForTopicSlug($dataSource, LicenceClass::CLASS_N, $topicSlug);
    }

    /**
     * @param DataSourceV1 $dataSource
     * @param string $licenceClass
     * @param string $topicSlug
     * 
     * @return Response
     */
    private function renderQuestionListForTopicSlug(
      DataSourceV1 $dataSource,
      string $licenceClass, 
      string $topicSlug
    ): Response 
    {
      if ($topicSlug === 'vse') {
        // special case of artifical 'all questions' topic
        $questionIds = $dataSource->getAllQuestionIds([$licenceClass]);
        $topicName = 'Všechny otázky';
      } else {
        $topic = $dataSource->getTopicBySlug($topicSlug);
        $questionIds = $dataSource->getQuestionIdsFromTopic($topic->topicId, [$licenceClass]);
        $topicName = $topic->name;
      }

      $questions = $dataSource->getQuestionsByIds($questionIds, false);

      $response = $this->render('questionListing/questionList.html.twig', [
        'topicName'                   => $topicName,
        'questions'                   => $questions,
        'questionCount'               => count($questions),
        'licenceClass'                => $licenceClass,
        'licenceClassString'          => LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass],
        'licenceClassStringLowerCase' => strtolower(LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass]),
      ]);

      return $response;
    }

    /**
     * @param DataSourceV1 $dataSource
     * @param string $licenceClass
     * 
     * @return Response
     */
    private function renderTopicListForLicenceClass(DataSourceV1 $dataSource, string $licenceClass): Response {
      $groupedTopics = $dataSource->getGroupedTopics([$licenceClass]);
      $allQuestionIds = $dataSource->getAllQuestionIds([$licenceClass]);

      match ($licenceClass) {
        LicenceClass::CLASS_A => $questionListRoute = 'harec-listing-topic-questions',
        LicenceClass::CLASS_N => $questionListRoute = 'novice-listing-topic-questions',
        default => throw new \InvalidArgumentException('Unknown licence class supplied: ' . $licenceClass),
      };
      
      $response = $this->render('questionListing/topicList.html.twig', [
        'licenceClass'       => $licenceClass,
        'licenceClassString' => LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass],
        'groupedTopics'      => $groupedTopics,
        'questionListRoute'  => $questionListRoute,
        'questionIdsTotal'   => count($allQuestionIds),
      ]);

      return $response;
    }

}