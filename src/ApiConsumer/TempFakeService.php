<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 7/18/14
 * Time: 6:05 PM
 */

namespace ApiConsumer;

use ApiConsumer\Scraper\Metadata;
use ApiConsumer\Scraper\Scraper;

class TempFakeService
{

    private $scraper;

    public function __construct(Scraper $scraper)
    {

        $this->scraper = $scraper;
    }

    /**
     * @param $linksGroupedByUser
     * @return array
     */
    public function processLinks(array $linksGroupedByUser = array())
    {

        $processedLinks = array();

        foreach ($linksGroupedByUser as $user => $userLinks) {

            $userProcessedLinks = $this->processUserLinks($userLinks);

            $processedLinks[$user] = $userProcessedLinks;

        }

        return $processedLinks;
    }

    /**
     * @param $userLinks
     * @return array
     */
    private function processUserLinks($userLinks)
    {

        $processedLinks = array();

        foreach ($userLinks as $link) {

            $metadata = $this->getMetadata($link['url']);

            $metaTags = $metadata->getMetaTags();

            $metaOgData = $metadata->extractOgMetadata($metaTags);

            if (array() !== $metaOgData) {
                $link = $metadata->mergeLinkMetadata($metaOgData, $link);
            } else {
                $metaDefaultData = $metadata->extractDefaultMetadata($metaTags);
                if (array() !== $metaDefaultData) {
                    $link = $metadata->mergeLinkMetadata($metaDefaultData, $link);
                }
            }

            $processedLinks[] = $link;
        }

        return $processedLinks;
    }

    /**
     * @param $url
     * @throws \Exception
     * @return \ApiConsumer\Scraper\Metadata
     */
    private function getMetadata($url)
    {

        try {
            $crawler = $this->scraper->initCrawler($url)->scrap('//meta | //title');

            return new Metadata($crawler);
        } catch (\Exception $e) {
            throw $e;
        }
    }
} 
