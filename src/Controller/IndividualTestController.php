<?php
declare (strict_types=1);

namespace App\Controller;

use App\Logic\IndividualTestComposer;
use App\Logic\IndividualTestEvaluator;
use App\Entity\LicenceClass;
use App\Utils\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class IndividualTestController extends AbstractController
{

    #[Route('/harec/test', name: 'harec-individual-test')]
    public function harecTest(IndividualTestComposer $testComposer): Response
    {
      return $this->renderTestPage($testComposer, LicenceClass::CLASS_A);
    }
    
    #[Route('/novice/test', name: 'novice-individual-test')]
    public function noviceTest(IndividualTestComposer $testComposer): Response
    {      
      return $this->renderTestPage($testComposer, LicenceClass::CLASS_N);
    }

    #[Route('/test/resume', name: 'resume-existing-test', methods: ['POST'])]
    public function resumeExistingTest(Request $request): Response
    {      
      return $this->resumeTest($request);
    }

    #[Route('/harec/test/vyhodnoceni', name: 'harec-individual-test-result', methods: ['POST'])]
    public function harecTestResult(Request $request, IndividualTestEvaluator $testEvaluator): Response
    {      
      return $this->renderTestResultPage($request, $testEvaluator);
    }

    #[Route('/novice/test/vyhodnoceni', name: 'novice-individual-test-result', methods: ['POST'])]
    public function noviceTestResult(Request $request, IndividualTestEvaluator $testEvaluator): Response
    {      
      return $this->renderTestResultPage($request, $testEvaluator);
    }

    /**
     * @param IndividualTestComposer $testComposer
     * @param string $licenceClass
     * 
     * @return Response
     */
    private function renderTestPage(IndividualTestComposer $testComposer, string $licenceClass): Response {
      $test = $testComposer->prepareTest($licenceClass);
      
      $response = $this->render('test/individual.html.twig', [
        'licenceClass'                => $licenceClass,
        'licenceClassString'          => LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass],
        'licenceClassStringLowerCase' => strtolower(LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass]),
        'test'                        => $test,
        'testJson'                    => json_encode($test),
      ]);

      return $response;
    }

    /**
     * @param Request $request
     * 
     * @return Response
     */
    private function resumeTest(Request $request): Response {
      try {
        $test = json_decode(base64_decode($request->get('test')), true);
        $licenceClass = $test['licenceClass'];

        $responseHtml = $this->renderView('test/parts/testContent.html.twig', [
          'licenceClass'       => $licenceClass,
          'licenceClassString' => LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass],
          'test'               => $test,
        ]);
        
        $responsePayload = [
          'responseHtml' => $responseHtml,
          'rawTestContent' => $test,
        ];

        return JsonResponse::prepareOkJsonResponse(json_encode($responsePayload), false);
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, false
        );
        $response->send();
        return $response;
      }
    }

    /**
     * @param IndividualTestEvaluator $testEvaluator
     * @param Request $request
     * 
     * @return Response
     */
    private function renderTestResultPage(
      Request $request,
      IndividualTestEvaluator $testEvaluator, 
    ): Response {
      $answers = json_decode(base64_decode($request->get('answers')), true);
      $test = json_decode(base64_decode($request->get('test')), true);
      $licenceClass = $test['licenceClass'];

      try {
        $result = $testEvaluator->evaluateTest($answers, $test);

        $response = $this->render('test/individualResult.html.twig', [
          'licenceClass'       => $licenceClass,
          'licenceClassString' => LicenceClass::CLASS_TO_CLASS_NAME[$licenceClass],
          'test'               => $result->test,
          'userAnswers'        => $result->answers,
          'evaluation'         => $result->result,
        ]);
        return $response;
      } catch (\InvalidArgumentException $e) {
        $response = JsonResponse::prepareErrorJsonResponse(
          $e->getMessage(), Response::HTTP_BAD_REQUEST, false
        );
        $response->send();
        return $response;
      }
    }

}