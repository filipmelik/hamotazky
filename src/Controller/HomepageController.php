<?php
declare (strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class HomepageController extends AbstractController
{

    #[Route('/', name: 'homepage')]
    public function homepage(): Response
    {
      $response = $this->render('homepage.html.twig');

      return $response;
    }
}