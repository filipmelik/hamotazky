<?php
declare (strict_types=1);

namespace App\Controller;

use App\Logic\DatasourceJsonExport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class DownloadController extends AbstractController
{

    #[Route('/download/sqlite', name: 'download-sqlite-questions')]
    public function downloadSqliteQuestions(): Response
    {
      $fileStorageDir = $this->getParameter('app.filestorageDir');
      $filePath = sprintf('%s%s%s', $fileStorageDir, DIRECTORY_SEPARATOR, 'hamtest.sqlite3');

      return $this->file($filePath);
    }

    #[Route('/download/json', name: 'download-json-questions')]
    public function downloadJsonQuestions(DatasourceJsonExport $dataSourceJsonExport): Response
    {
      $json = $dataSourceJsonExport->getJson();

      $response = new Response($json);
      $response->headers->set('Content-Encoding', 'UTF-8');
      $response->headers->set('Content-Type', 'application/json');
      $response->headers->set('Content-Disposition', 'attachment; filename=hamtest.json');

      return $response;

    }
}