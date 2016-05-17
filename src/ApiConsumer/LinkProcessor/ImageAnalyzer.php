<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace ApiConsumer\LinkProcessor;


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

        if (!empty($images))
        {
            return $images[0];
        }

        return null;
    }

    public function isValidImage($url)
    {
        $imageArray = $this->buildResponseArray($url);
        return $this->isImageResponseValid($imageArray);
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
        $response = $this->client->head($imageUrl);
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