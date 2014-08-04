<?php

namespace ApiConsumer\Scraper;

class LinkProcessor
{

    /** @var \ApiConsumer\Scraper\Scraper */
    private $scraper;

    /**
     * @param Scraper $scraper
     */
    public function __construct(Scraper $scraper)
    {

        $this->scraper = $scraper;
    }

    /**
     * @param array $link
     * @return array
     */
    public function processLink(array $link)
    {

        $url = $link['url'];

        $basicMetadata = $this->scrapBasicMetadata($url);
        if (array() !== $basicMetadata) {
            $link = $this->overrideLinkDataWithScrapedData($basicMetadata, $link);
        }

        $fbMetadata = $this->scrapFacebookMetadata($url);
        if (array() !== $fbMetadata) {
            $link = $this->overrideLinkDataWithScrapedData($fbMetadata, $link);
        }

        return $link;
    }

    /**
     * @param $url
     * @return array|mixed
     */
    public function scrapBasicMetadata($url)
    {

        $crawler = $this->getCrawler($url);

        $basicMetadata = new BasicMetadata($crawler);

        $metaTags = $basicMetadata->getMetaTags();

        $metadata = $basicMetadata->extractDefaultMetadata($metaTags);
        $metadata[]['tags'] = $basicMetadata->extractTagsFromKeywords($metaTags);

        return $metadata;
    }

    /**
     * @param $url
     * @return array|mixed
     */
    public function scrapFacebookMetadata($url)
    {

        $crawler = $this->getCrawler($url);

        $fbMetadata = new FacebookMetadata($crawler);
        $metaTags = $fbMetadata->getMetaTags();

        $metadata = $fbMetadata->extractOgMetadata($metaTags);
        $metadata[]['tags'] = $fbMetadata->extractTagsFromFacebookMetadata($metaTags);

        return $metadata;
    }

    /**
     * @param $scrapedData
     * @param $link
     * @return mixed
     */
    private function overrideLinkDataWithScrapedData(array $scrapedData, array $link)
    {

        foreach ($scrapedData as $meta) {
            if (array_key_exists('title', $meta) && null !== $meta['title']) {
                $link['title'] = $meta['title'];
            }

            if (false === array_key_exists('description', $link)) {
                $link['description'] = "";
            }

            if (array_key_exists('description', $meta) && null !== $meta['description']) {
                $link['description'] = $meta['description'];
            }

            if (array_key_exists('canonical', $meta) && null !== $meta['canonical']) {
                $link['url'] = $meta['canonical'];
            }

            if (array_key_exists('tags', $meta)) {
                if (!array_key_exists('tags', $link)) {
                    $link['tags'] = array();
                }
                $link['tags'] = array_merge($link['tags'], $meta['tags']);
            }
        }

        return $link;
    }

    /**
     * @param $url
     * @throws \Exception
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getCrawler($url)
    {
        try {
            $crawler = $this->scraper->initCrawler($url)->scrap('//meta | //title');
            return $crawler;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
