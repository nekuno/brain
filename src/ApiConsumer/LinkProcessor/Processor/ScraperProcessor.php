<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;
use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
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

    protected $parser;
    /**
     * @param UrlParser $urlParser
     * @param Client $client
     * @param \ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser $basicMetadataParser
     * @param \ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser $facebookMetadataParser
     */
    public function __construct(
        UrlParser $urlParser,
        Client $client,
        BasicMetadataParser $basicMetadataParser,
        FacebookMetadataParser $facebookMetadataParser
    )
    {
        $this->parser = $urlParser;
        $this->client = $client;
        $this->basicMetadataParser = $basicMetadataParser;
        $this->facebookMetadataParser = $facebookMetadataParser;
    }

    /**
     * @inheritdoc
     */
    public function process(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        $url = $preprocessedLink->getCanonical();
        $link['url'] = $url;

        try {
            $crawler = $this->client->request('GET', $url);
        } catch (\LogicException $e) {
            $link['processed'] = 0;
            return $link;
        } catch (RequestException $e) {
            $link['processed'] = 0;
            return $link;
        }

        $responseHeaders = $this->client->getResponse()->getHeaders();
        if ($responseHeaders) {
            if (isset($responseHeaders['Content-Type'][0]) && false !== strpos($responseHeaders['Content-Type'][0], "image/")) {
                $link['additionalLabels'] = array('Image');
            }
        }

        $basicMetadata = $this->basicMetadataParser->extractMetadata($crawler);
        $basicMetadata['thumbnail'] = $this->selectImage($basicMetadata['images']);
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

        $this->overrideAttribute('title', $link, $scrapedData);
        $this->overrideAttribute('description', $link, $scrapedData);
        $this->overrideAttribute('language', $link, $scrapedData);
        $this->overrideAttribute('thumbnail', $link, $scrapedData);

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

    private function overrideAttribute($name, &$link, $scrapedData)
    {
        if (array_key_exists($name, $scrapedData)) {
            if (null !== $scrapedData[$name] && "" !== $scrapedData[$name]) {
                $link[$name] = $scrapedData[$name];
            }
        }
    }

    private function selectImage(array $imageUrls)
    {
        $images=array();
        foreach ($imageUrls as $key=> $imageUrl) {
            $images[$key] = $this->buildResponseArray($imageUrl);
        }

        $images = $this->sortImages($images);

        if (!empty($images))
        {
            return $images[0];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getParser()
    {
        return $this->parser;
    }

    public function isValidImage($url)
    {
        $imageArray = $this->buildResponseArray($url);
        return $this->isImageResponseValid($imageArray);
    }

    private function buildResponseArray($imageUrl)
    {
        $response = $this->client->getClient()->head($imageUrl);
        $image = array(
            'url' => $imageUrl,
            'status' => $response->getStatusCode(),
            'type' => $response->getHeader('Content-Type'),
            'length' => intval($response->getHeader('Content-Length'))
        );
        return $image;
    }

    private function sortImages(array $images)
    {
        $lengths = array();
        foreach ($images as $key => $image){
            if (!$this->isImageResponseValid($image)) {
                unset($images[$key]);
            }
            $lengths[$key] = $image['length'];
        }

        array_multisort($lengths, SORT_DESC, $images);

        return $images;
    }

    private function isImageResponseValid(array $image){
        if (!(200 <= $image['code'] && $image['code'] < 300 //status code
            && strpos($image['type'], 'image') !== false  //image type
            && $image['length']!==0 && 10000 < $image['length'] && 200000 > $image['length'])) { //image size
            return false;
        }

        return true;
    }
}
