<?php

namespace ApiConsumer\Images;


use GuzzleHttp\Client;

class ImageAnalyzer
{
    private $timeToReprocess;

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->timeToReprocess = 1000*3600*24*7; //1 week in milliseconds
    }

    public function selectImage(array $imageUrls)
    {
        $images=array();
        foreach ($imageUrls as $key=> $imageUrl) {
            $images[$key] = $this->buildResponseArray($imageUrl);
        }

        $images = $this->sortImages($images);

        return empty($images) ? null : reset($images);
    }

    public function isValidImage($url)
    {
        $imageArray = $this->buildResponseArray($url);
        return $imageArray->isValid();
    }

    public function filterToReprocess(array $links)
    {
        foreach ($links as $key => $link){
            //we save timestamps in neo4j as milliseconds
            $imageTimestamp = isset($link['imageProcessed'])? $link['imageProcessed'] : 1;
            $nowTimestamp = (new \DateTime())->getTimestamp() * 1000 ;
            if ($imageTimestamp < ( $nowTimestamp - $this->timeToReprocess)){
                continue;
            }

            $thumbnail = isset($link['additionalLabels']) & in_array('Image', array($link['additionalLabels'])) ? $link['url']
                : isset($link['thumbnail']) ? $link['thumbnail'] : null;
            if (empty($thumbnail)){
                continue;
            }
            if (!$this->isValidImage($thumbnail)){
                continue;
            }

            unset($links[$key]);
        }

        return $links;
    }

    private function buildResponseArray($imageUrl)
    {
        $head = $this->client->head($imageUrl);
        $response = new ImageResponse();
        $response->setUrl($imageUrl);
        $response->setStatusCode($head->getStatusCode());
        $response->setType($head->getHeader('Content-Type'));
        $response->setLength(intval($head->getHeader('Content-Length')));

        return $response;
    }

    /**
     * @param ImageResponse[] $images
     * @return ImageResponse[]
     */
    private function sortImages(array $images)
    {
        $lengths = array();
        foreach ($images as $key => $image){
            if (!$image->isValid()) {
                unset($images[$key]);
            }
            $lengths[$key] = $image['length'];
        }

        array_multisort($lengths, SORT_DESC, $images);

        return $images;
    }
}