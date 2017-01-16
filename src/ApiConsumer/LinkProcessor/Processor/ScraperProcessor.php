<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Factory\GoutteClientFactory;
use ApiConsumer\Images\ImageResponse;
use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use Goutte\Client;
use GuzzleHttp\Exception\RequestException;
use Model\Image;
use Model\Link;
use Symfony\Component\DomCrawler\Crawler;

class ScraperProcessor implements ProcessorInterface
{
    /**
     * @var GoutteClientFactory
     */
    protected $clientFactory;

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
     * @param GoutteClientFactory $goutteClientFactory
     * @param \ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser $basicMetadataParser
     * @param \ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser $facebookMetadataParser
     */
    public function __construct(
        GoutteClientFactory $goutteClientFactory,
        BasicMetadataParser $basicMetadataParser,
        FacebookMetadataParser $facebookMetadataParser
    ) {
        $this->clientFactory = $goutteClientFactory;
        $this->client = $this->clientFactory->build();
        $this->basicMetadataParser = $basicMetadataParser;
        $this->facebookMetadataParser = $facebookMetadataParser;
    }

    public function getResponse(PreprocessedLink $preprocessedLink)
    {
        $url = $preprocessedLink->getUrl();

        try {
            $this->client->getClient()->setDefaultOption('timeout', 30.0);
            $crawler = $this->client->request('GET', $url);
        } catch (\LogicException $e) {
            $this->client = $this->clientFactory->build();
            throw new CannotProcessException($url);
        } catch (RequestException $e) {
            $this->client = $this->clientFactory->build();
            throw new CannotProcessException($url);
        }

        $imageResponse = new ImageResponse($url, 200, $this->client->getResponse()->getHeader('Content-Type'));
        if ($imageResponse->isImage()) {
            $image = Image::buildFromArray($preprocessedLink->getFirstLink()->toArray());
            $preprocessedLink->setFirstLink($image);
        }

        return array('html' => $crawler->html());
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();

        $crawler = new Crawler();
        $crawler->addHtmlContent($data['html']);

        $basicMetadata = $this->basicMetadataParser->extractMetadata($crawler);
        $this->overrideFieldsData($link, $basicMetadata);

        $fbMetadata = $this->facebookMetadataParser->extractMetadata($crawler);
        $this->overrideFieldsData($link, $fbMetadata);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($data['html']);

        $images = $this->basicMetadataParser->getImages($crawler);

        $url = $preprocessedLink->getUrl();
        $this->fixRelativeUrls($images, $url);

        return $images;
    }

    private function fixRelativeUrls(array &$images, $url)
    {
        if ($this->isRelativeUrl($url)) {
            return;
        }

        $slashPosition = strpos($url, '/');

        $prefix = $slashPosition ? substr($url, 0, $slashPosition) : $url;

        foreach ($images as &$imageUrl) {
            if ($this->isRelativeUrl($imageUrl)) {
                $imageUrl = $prefix . $imageUrl;
            }
        }
    }

    private function isRelativeUrl($url)
    {
        $startsWithSlash = strpos($url, '/') === 0;

        $startsWithDoubleSlash = substr($url, 0, 2) === '//';

        return $startsWithSlash && !$startsWithDoubleSlash;
    }

    private function overrideFieldsData(Link $link, array $scrapedData)
    {
        foreach (array('title', 'description', 'language', 'thumbnail') as $field) {
            if (!isset($scrapedData[$field]) || empty($scrapedData[$field])) {
                continue;
            }

            $setter = 'set' . ucfirst($field);
            $link->$setter($scrapedData[$field]);
        }
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();
        $crawler = new Crawler();
        $crawler->addHtmlContent($data['html']);

        $basicMetadata['tags'] = $this->basicMetadataParser->extractTags($crawler);
        $this->addScrapedTags($link, $basicMetadata);

        $basicMetadata['tags'] = $this->facebookMetadataParser->extractTags($crawler);
        $this->addScrapedTags($link, $basicMetadata);
    }

    private function addScrapedTags(Link $link, array $scrapedData)
    {
        if (array_key_exists('tags', $scrapedData) && is_array($scrapedData['tags'])) {

            foreach ($scrapedData['tags'] as $tag) {
                $link->addTag($tag);
            }
        }
    }

    public function getSynonymousParameters(PreprocessedLink $preprocessedLink, array $data)
    {
        return new SynonymousParameters();
    }
}
