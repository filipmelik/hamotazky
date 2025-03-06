<?php
declare (strict_types=1);

namespace App\Utils;

use Symfony\Component\HttpFoundation\Response;

class JsonResponse {

    const JSON_CONTENT_TYPE = 'application/json';

    /**
     * @param string $data
     * @param bool $setCorsHeaders
     * @return Response
     */
    static function prepareOkJsonResponse(?string $jsonData, bool $setCorsHeaders): Response {
        $response = new Response();
        $response->headers->set('Content-Type', self::JSON_CONTENT_TYPE);

        if ($setCorsHeaders) {
            $response = self::setCorsHeaders($response);
        }

        $response->setStatusCode(Response::HTTP_OK);
        if ($jsonData !== null) {
          $response->setContent($jsonData);
        }
  
        return $response;
      }
  
      /**
       * @param string $message
       * @param integer $statusCode
       * @param bool $setCorsHeaders
       * @return Response
       */
      static function prepareErrorJsonResponse(string $message, int $statusCode, bool $setCorsHeaders): Response {
        $response = new Response();
        $response->headers->set('Content-Type', self::JSON_CONTENT_TYPE);

        if ($setCorsHeaders) {
            $response = self::setCorsHeaders($response);
        }

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
     * Set CORS headers to request repsonse so the API endpoints can be called out of its original domain
     *
     * @param Response $response
     * @return Response modified Response
     */
    private static function setCorsHeaders(Response $response): Response
    {
      $response->headers->set('Access-Control-Allow-Origin', '*');
      $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
      $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

      return $response;
    }

}