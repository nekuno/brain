<?php

namespace ApiConsumer\Scraper;

class LinkProcessor
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

            foreach ($userLinks as $link) {

                $userProcessedLinks[] = $this->hydrateLinkFromScrapedMetadata($link);
            }

            $processedLinks[$user] = $userProcessedLinks;

        }

        return $processedLinks;
    }

    /**
     * @param $link
     * @return array
     */
    private function hydrateLinkFromScrapedMetadata($link)
    {

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

        return $link;
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
