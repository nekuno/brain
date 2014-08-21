<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\MetadataParserInterface;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class ScraperProcessor implements ProcessorInterface
{
    private $facebookMetadataParser;
    private $basicMetadataParser;
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client, MetadataParserInterface $basicMetadataParser, MetadataParserInterface $facebookMetadataParser)
    {
        $this->client = $client;
        $this->basicMetadataParser = $basicMetadataParser;
        $this->facebookMetadataParser = $facebookMetadataParser;

    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        $url = $link['url'];

        $crawler = $this->client->request('GET', $url)->filterXPath('//meta | //title');

        $metadataTags = $crawler->each(
            function (Crawler $node) {
                return array(
                    'rel' => $node->attr('rel'),
                    'name' => $node->attr('name'),
                    'content' => $node->attr('content'),
                    'property' => $node->attr('property'),
                    'content' => $node->attr('content'),
                );
            }
        );

        $basicMetadata = $this->scrapBasicMetadata($metadataTags);
        if (array() !== $basicMetadata) {
            $link = $this->overrideLinkDataWithScrapedData($basicMetadata, $link);
        }

        $fbMetadata = $this->scrapFacebookMetadata($metadataTags);
        if (array() !== $fbMetadata) {
            $link = $this->overrideLinkDataWithScrapedData($fbMetadata, $link);
        }

        return $link;
    }

    public function scrapBasicMetadata($metaTags)
    {

        $metadata = $this->basicMetadataParser->extractMetadata($metaTags);
        $metadata[]['tags'] = $this->basicMetadataParser->extractTags($metaTags);

        return $metadata;
    }

    public function scrapFacebookMetadata($metaTags)
    {
        $metadata = $this->facebookMetadataParser->extractMetadata($metaTags);
        $metadata[]['tags'] = $this->facebookMetadataParser->extractTags($metaTags);

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

}
