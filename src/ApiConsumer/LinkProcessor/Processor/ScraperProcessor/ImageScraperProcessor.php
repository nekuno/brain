<?php

namespace ApiConsumer\LinkProcessor\Processor\ScraperProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Factory\GoutteClientFactory;
use ApiConsumer\Images\ImageAnalyzer;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use GuzzleHttp\Exception\RequestException;
use Model\Link\Image;

class ImageScraperProcessor extends AbstractScraperProcessor
{
    protected $imageAnalyzer;

    public function __construct(GoutteClientFactory $goutteClientFactory, $brainBaseUrl)
    {
        parent::__construct($goutteClientFactory);
        $guzzle = $this->client->getClient();
        $this->imageAnalyzer = new ImageAnalyzer($guzzle);
    }

    public function getResponse(PreprocessedLink $preprocessedLink)
    {
        $url = $preprocessedLink->getUrl();

        try {
            $imageResponse = $this->imageAnalyzer->buildResponse($url);
        } catch (\LogicException $e) {
            $this->client = $this->clientFactory->build();
            throw new CannotProcessException($url);
        } catch (RequestException $e) {
            $this->client = $this->clientFactory->build();
            throw new CannotProcessException($url);
        }
        return $imageResponse->toArray();
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $image = Image::buildFromLink($preprocessedLink->getFirstLink());
        $preprocessedLink->setFirstLink($image);
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        return array();
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        return array();
    }
}