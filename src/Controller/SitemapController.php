<?php

namespace App\Controller;

use App\DataSource\DataSourceV1;
use App\Entity\LicenceClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{

    #[Route('/sitemap.{_format}', name: 'app_sitemap', requirements: ['_format' => 'html|xml'], format: 'xml')]
    public function index(Request $request, DataSourceV1 $dataSource): Response
    {
        $hostname = $request->getSchemeAndHttpHost();

        $urls = []; 
    
        $urls[] = [
            'loc' => $this->generateUrl('homepage'), 
            'priority' => '1.00'
        ];
        $urls[] = [
            'loc' => $this->generateUrl('harec-listing-topic-list'), 
            'priority' => '0.90'
        ];
        $urls[] = [
            'loc' => $this->generateUrl('novice-listing-topic-list'), 
            'priority' => '0.90'
        ];
        $urls[] = [
            'loc' => sprintf("%sapi/v1/docs/", $this->generateUrl('homepage')), 
            'priority' => '0.60'
        ];

        // listing uls
        $harecTopicUrls = $this->generateQuestionListingOrPracticeUrls(
            $dataSource, 
            'listing',
            LicenceClass::CLASS_A,
            '0.80',
        );
        $urls = array_merge($urls, $harecTopicUrls);

        $noviceTopicUrls = $this->generateQuestionListingOrPracticeUrls(
            $dataSource, 
            'listing',
            LicenceClass::CLASS_N,
            '0.80',
        );
        $urls = array_merge($urls, $noviceTopicUrls);

        // practice urls
        $harecTopicUrls = $this->generateQuestionListingOrPracticeUrls(
            $dataSource, 
            'practice',
            LicenceClass::CLASS_A,
            '0.65',
        );
        $urls = array_merge($urls, $harecTopicUrls);

        $noviceTopicUrls = $this->generateQuestionListingOrPracticeUrls(
            $dataSource, 
            'practice',
            LicenceClass::CLASS_N,
            '0.65',
        );
        $urls = array_merge($urls, $noviceTopicUrls);
       
        $xml = $this->renderView('sitemap.xml.twig', [
            'urls' => $urls,
            'hostname' => $hostname
        ]);

        return new Response($xml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Create sitemap url entries of given priority for given licence class for question list of given topic
     *
     * @param DataSourceV1 $dataSource
     * @param string $mode
     * @param string $licenceClass
     * @param string $priority
     * @return array
     */
    private function generateQuestionListingOrPracticeUrls(
        DataSourceV1 $dataSource, 
        string $mode,
        string $licenceClass, 
        string $priority,
    ): array
    {
        match ($licenceClass) {
            LicenceClass::CLASS_A => $questionListRoute = "harec-{$mode}-topic-questions",
            LicenceClass::CLASS_N => $questionListRoute = "novice-{$mode}-topic-questions",
            default => throw new \InvalidArgumentException('Unknown licence class supplied: ' . $licenceClass),
        };

        $urls = []; 

        // manually add special case of artifical 'all' topic
        $urls[] = [
            'loc' => $this->generateUrl($questionListRoute, ['topicSlug' => 'vse']), 
            'priority' => $priority,
        ];

        $topics = $dataSource->getAllTopics([$licenceClass]);

        foreach($topics as $t) {
            $urls[] = [
                'loc' => $this->generateUrl($questionListRoute, ['topicSlug' => $t->topicSlug]), 
                'priority' => $priority,
            ];
        }

        return $urls;
    }
}