<?php
declare (strict_types=1);

namespace App\Controller;

use App\DataSource\DataSourceV1;
use App\Entity\LicenceClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves /llms.txt (https://llmstxt.org) - markdown overview of the site for LLMs and AI agents
 */
class LlmsTxtController extends AbstractController
{

    #[Route('/llms.txt', name: 'llms-txt', methods: ['GET'])]
    public function index(DataSourceV1 $dataSource): Response
    {
      $content = $this->renderView('llms.txt.twig', [
        'harecTopics'  => $dataSource->getAllTopics([LicenceClass::CLASS_A]),
        'noviceTopics' => $dataSource->getAllTopics([LicenceClass::CLASS_N]),
      ]);

      $response = new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
      return $response;
    }

}
