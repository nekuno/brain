<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 3/07/14
 * Time: 22:18
 */

namespace Social\Web\Scraper;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class Scraper
{

    private $client;

    private $url;

    public function __construct(Client $client, $url = null)
    {

        $this->client = $client;

        $this->url = $url;

    }

    public function scrap()
    {

        if (null === $this->url) {
            throw new \InvalidArgumentException('The URL can not be empty');
        }

        $crawler = $this->client->request('GET', $this->url);

        $htmlMetaTags = $crawler->filterXPath('//head/meta | //title');

        $metaTagsMetadata = $this->extractMetaTagsMetadata($htmlMetaTags);

        $ogMetadata = $this->extractOgMetadata($metaTagsMetadata);

        if (array() !== $ogMetadata) {
            return $ogMetadata;
        }

        $defaultMetadata[] = array('title' => $htmlMetaTags->filter('title')->text());

        $defaultMetadata = array_merge($defaultMetadata, $this->extractDefaultMetadata($metaTagsMetadata));

        if (array() !== $defaultMetadata) {
            return $defaultMetadata;
        }

        return array();

    }

    /**
     * @param $metadata
     * @return array
     */
    protected function extractDefaultMetadata($metadata)
    {

        $validKeywords = array('author', 'title', 'description', 'canonical');

        $defaultMetadata = array();

        foreach ($metadata as $nodeMetadata) {
            if ('' !== $nodeMetadata['name']) {
                if (in_array($nodeMetadata['name'], $validKeywords)) {
                    $defaultMetadata[] = array($nodeMetadata['name'] => $nodeMetadata['content']);
                }
            }
        }

        $this->trimNullValuesAndReindexArray($defaultMetadata);

        return $defaultMetadata;
    }

    /**
     * @param $metadata
     * @return array
     */
    protected function extractOgMetadata($metadata)
    {

        $ogMetadata = array();

        foreach ($metadata as $nodeMetadata) {
            if (strstr($nodeMetadata['property'], 'og:')) {
                $ogMetadata[] = array($nodeMetadata['property'] => $nodeMetadata['content']);
            }
        }

        $this->trimNullValuesAndReindexArray($ogMetadata);

        return $ogMetadata;
    }

    /**
     * @param $metaTags
     */
    protected function extractMetaTagsMetadata($metaTags)
    {
        $metadata = $metaTags->each(function (Crawler $node) {

            return array(
                'rel'      => $node->attr('rel'),
                'name'     => $node->attr('name'),
                'property' => $node->attr('property'),
                'content'  => $node->attr('content'),
            );

        });

        return $metadata;
    }

    /**
     * @param $nodeWithOgMetadata
     * @return array
     */
    protected function trimNullValuesAndReindexArray(&$nodeWithOgMetadata)
    {
        foreach ($nodeWithOgMetadata as $k => $v) {
            if ($v === null) {
                unset($nodeWithOgMetadata[$k]);
            }
        }

        return array_values($nodeWithOgMetadata);

    }

}
