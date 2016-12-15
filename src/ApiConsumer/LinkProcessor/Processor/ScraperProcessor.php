<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Images\ImageAnalyzer;
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
    ) {
        $this->client = $client;
        $this->basicMetadataParser = $basicMetadataParser;
        $this->facebookMetadataParser = $facebookMetadataParser;
    }

    public function requestItem(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        $url = $preprocessedLink->getUrl();
        $link->setUrl($url);

        try {
            $this->client->getClient()->setDefaultOption('timeout', 30.0);
            $crawler = $this->client->request('GET', $url);
        } catch (\LogicException $e) {
            //log
            throw new CannotProcessException($url);
        } catch (RequestException $e) {
            throw new CannotProcessException($url);
        }

        $responseHeaders = $this->client->getResponse()->getHeaders();
        //TODO: unify with LinkResolver->isCorrectImageResponse (QS-800)
        if ($responseHeaders && isset($responseHeaders['Content-Type'][0]) && false !== strpos($responseHeaders['Content-Type'][0], "image/")) {
            $image = Image::buildFromArray($link->toArray());
            $preprocessedLink->setLink($image);
        }

        return array('html' => $crawler->html());
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getLink();

        $crawler = new Crawler();
        $crawler->addHtmlContent($data['html']);

        $basicMetadata = $this->basicMetadataParser->extractMetadata($crawler);
        $this->overrideFieldsData($link, $basicMetadata);

        $fbMetadata = $this->facebookMetadataParser->extractMetadata($crawler);
        $this->overrideFieldsData($link, $fbMetadata);
    }

    function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($data['html']);

        $basicMetadata = $this->basicMetadataParser->extractMetadata($crawler);
        return $basicMetadata['images'];
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
        $link = $preprocessedLink->getLink();
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

    public function getNewUrls(array $data)
    {
        return array();
    }
}
