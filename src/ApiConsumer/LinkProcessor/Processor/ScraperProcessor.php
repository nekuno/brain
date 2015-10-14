<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use Goutte\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class ScraperProcessor implements ProcessorInterface
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var FacebookMetadataParser
     */
    private $facebookMetadataParser;

    /**
     * @var BasicMetadataParser
     */
    private $basicMetadataParser;

    /**
     * @param Client $client
     * @param \ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser $basicMetadataParser
     * @param \ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser $facebookMetadataParser
     */
    public function __construct(
        Client $client,
        BasicMetadataParser $basicMetadataParser,
        FacebookMetadataParser $facebookMetadataParser
    )
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

        try {
            $crawler = $this->client->request('GET', $url);
        } catch (RequestException $e) {
            $link['processed'] = 0;
            return $link;
        } catch (\LogicException $e) {
            return $link;
        }

        $responseHeaders =$this->client->getResponse()->getHeaders();
        if ($responseHeaders) {
            if (isset($responseHeaders['Content-Type'][0]) && false !== strpos($responseHeaders['Content-Type'][0], "image/")) {
                $link['additionalLabels'] = array('Image');
            }
        }

        $basicMetadata = $this->basicMetadataParser->extractMetadata($crawler);
        $basicMetadata['tags'] = $this->basicMetadataParser->extractTags($crawler);
        $link = $this->overrideLinkDataWithScrapedData($link, $basicMetadata);

        $fbMetadata = $this->facebookMetadataParser->extractMetadata($crawler);
        $fbMetadata['tags'] = $this->facebookMetadataParser->extractTags($crawler);
        $link = $this->overrideLinkDataWithScrapedData($link, $fbMetadata);

        return $link;
    }

    /**
     * @param array $link
     * @param array $scrapedData
     * @return array
     */
    private function overrideLinkDataWithScrapedData(array $link, array $scrapedData = array())
    {

        if (array_key_exists('title', $scrapedData)) {
            if (null !== $scrapedData['title'] && "" !== $scrapedData['title']) {
                $link['title'] = $scrapedData['title'];
            }
        }

        if (array_key_exists('description', $scrapedData)) {
            if (null !== $scrapedData['description'] && "" !== $scrapedData['description']) {
                $link['description'] = $scrapedData['description'];
            }
        }

        if (array_key_exists('language', $scrapedData)) {
            if (null !== $scrapedData['language'] && "" !== $scrapedData['language']) {
                $link['language'] = $scrapedData['language'];
            }
        }

        if (array_key_exists('tags', $scrapedData)) {
            if (!array_key_exists('tags', $link)) {
                $link['tags'] = array();
            }
            foreach ($link['tags'] as $tag) {
                foreach ($scrapedData['tags'] as $sIndex => $sTag) {
                    if ($tag['name'] === $sTag['name']) {
                        unset($scrapedData['tags'][$sIndex]);
                    }
                }

            }

            $link['tags'] = array_merge($link['tags'], $scrapedData['tags']);
        }

        return $link;
    }
}
