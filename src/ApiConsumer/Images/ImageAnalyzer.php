<?php

namespace ApiConsumer\Images;

use GuzzleHttp\Client;

class ImageAnalyzer
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function selectImage(array $imageUrls)
    {
        $images = array();
        foreach ($imageUrls as $imageUrl) {
            if ($image = $this->isValidImage($imageUrl)) {
                $images[] = $image;
            }
        }

        $images = $this->sortImages($images);

        return empty($images) ? null : reset($images);
    }

    private function isValidImage($url)
    {
        if (empty($url)) {
            return false;
        }

        $image = $this->buildResponse($url);

        return $image->isValid() ? $image : null;
    }

    /**
     * @param ImageResponse[] $imageResponses
     * @return ImageResponse[]
     */
    private function sortImages(array $imageResponses)
    {
        $lengths = array();
        foreach ($imageResponses as $key => $image) {
            if (!$image->isValid()) {
                unset($imageResponses[$key]);
            } else {
                $lengths[$key] = $image->getLength();
            }
        }

        array_multisort($lengths, SORT_DESC, $imageResponses);

        return $imageResponses;
    }

    public function filterToReprocess(array $links)
    {
        foreach ($links as $key => $link) {
            if (!$this->needsReprocessing($link)) {
                unset($links[$key]);
            }
        }

        return $links;
    }

    private function needsReprocessing(array $link)
    {
        return $this->isImageOld($link) || !$this->isValidThumbnail($link);
    }

    private function isImageOld(array $link)
    {
        $timeToReprocess = 1000 * 3600 * 24 * 7; //1 week in milliseconds

        //we save timestamps in neo4j as milliseconds
        $imageTimestamp = isset($link['imageProcessed']) ? $link['imageProcessed'] : 1;
        $nowTimestamp = (new \DateTime())->getTimestamp() * 1000;

        return $imageTimestamp < ($nowTimestamp - $timeToReprocess);
    }

    private function isValidThumbnail($link)
    {
        $thumbnailUrl = $this->getThumbnailFromLink($link);

        return !$this->isValidImage($thumbnailUrl);
    }

    //this logic could go in Link->getImageUrl()
    private function getThumbnailFromLink($link)
    {
        return isset($link['additionalLabels']) && in_array('Image', array($link['additionalLabels'])) ? $link['url']
            : isset($link['thumbnail']) ? $link['thumbnail'] : null;
    }

    private function buildResponse($imageUrl)
    {
        $head = $this->client->head($imageUrl);
        $response = new ImageResponse();
        $response->setUrl($imageUrl);
        $response->setStatusCode($head->getStatusCode());
        $response->setType($head->getHeader('Content-Type'));
        $response->setLength(intval($head->getHeader('Content-Length')));

        return $response;
    }
}