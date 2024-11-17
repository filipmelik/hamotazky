<?php
declare (strict_types=1);

namespace App\Controller;

use App\Logic\IndividualTestComposer;
use App\Logic\IndividualTestEvaluator;
use App\Entity\LicenceClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


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
      } catch (\InvalidArgumentException $e) {
        $response = new JsonResponse(
          [
            'error' => $e->getMessage(),
          ], 
          Response::HTTP_BAD_REQUEST
        );
      }

      return $response;
    }

}