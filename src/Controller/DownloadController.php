<?php
declare (strict_types=1);

namespace App\Controller;

use App\Entity\LicenceClass;
use App\Logic\DatasourceJsonExport;
use App\Utils\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
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
    public function downloadJsonQuestions(
        Request $request, 
        DatasourceJsonExport $dataSourceJsonExport,
    ): Response
    {
      $licenceClassesFilter = Utils::prepareLicenceClassesFilter($request);
      $licenceClasses = empty($licenceClassesFilter) ? LicenceClass::ALL : $licenceClassesFilter;
      
      $json = $dataSourceJsonExport->getJson($licenceClasses);
      $outFileName = sprintf('hamtest-%s.json', implode('_', $licenceClasses));

      $response = new Response($json);
      $response->headers->set('Content-Encoding', 'UTF-8');
      $response->headers->set('Content-Type', 'application/json');
      $response->headers->set('Content-Disposition', "attachment; filename={$outFileName}");

      return $response;
    }
}