<?php
declare (strict_types=1);

namespace App\Controller;

use App\DataSource\DataSourceV1;
use App\Entity\LicenceClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class QuestionPracticeController extends AbstractController
{

    #[Route('/harec/procvicovani', name: 'harec-practice-topic-list')]
    public function harecPracticeTopicList(DataSourceV1 $dataSource): Response
    {
      return $this->renderTopicListForLicenceClass($dataSource, LicenceClass::CLASS_A);
    }

    #[Route('/harec/procvicovani/{topicSlug}', name: 'harec-practice-topic-questions')]
    public function harecQuestionPracticeIndex(string $topicSlug, DataSourceV1 $dataSource): Response
    {
      return $this->renderQuestionListForTopicSlug($dataSource, LicenceClass::CLASS_A, $topicSlug);
    }
    
    #[Route('/novice/procvicovani', name: 'novice-practice-topic-list')]
    public function novicePracticeTopicList(DataSourceV1 $dataSource): Response
    {      
      return $this->renderTopicListForLicenceClass($dataSource, LicenceClass::CLASS_N);
    }

    #[Route('/novice/procvicovani/{topicSlug}', name: 'novice-practice-topic-questions')]
    public function noviceQuestionPracticeIndex(string $topicSlug, DataSourceV1 $dataSource): Response
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
      string $topicSlug,
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
      
      $questions = $dataSource->getQuestionsByIds($questionIds);

      $response = $this->render('questionPractice/practice.html.twig', [
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
      $allQuestionIds = $dataSource->getAllQuestionIds([$licenceClass]);
      $groupedTopics = $dataSource->getGroupedTopics([$licenceClass]);

      match ($licenceClass) {
        LicenceClass::CLASS_A => $questionListRoute = 'harec-practice-topic-questions',
        LicenceClass::CLASS_N => $questionListRoute = 'novice-practice-topic-questions',
        default => throw new \InvalidArgumentException('Unknown licence class supplied: ' . $licenceClass),
      };
      
      $response = $this->render('questionPractice/topicList.html.twig', [
        'questionIdsTotal'   => count($allQuestionIds),
        'licenceClass'       => $licenceClass,
        'licenceClassString' => LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass],
        'groupedTopics'      => $groupedTopics,
        'questionListRoute'  => $questionListRoute,
      ]);

      return $response;
    }

}