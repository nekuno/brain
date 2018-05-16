<?php

namespace ApiConsumer\Images;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Model\Link\Image;
use Model\Link\Link;

class ImageAnalyzer
{
    protected $client;

    protected $responses = array();

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param ProcessingImage[] $processingImages
     * @return string|null
     */
    public function selectSmallThumbnail(array $processingImages)
    {
        return $this->selectThumbnail($processingImages, ProcessingImage::LABEL_SMALL, 100);
    }

    /**
     * @param ProcessingImage[] $processingImages
     * @return string|null
     */
    public function selectMediumThumbnail(array $processingImages)
    {
        return $this->selectThumbnail($processingImages, ProcessingImage::LABEL_MEDIUM, 300);
    }

    /**
     * @param ProcessingImage[] $processingImages
     * @return string|null
     */
    public function selectLargeThumbnail(array $processingImages)
    {
        $thumbnail = $this->selectThumbnail($processingImages, ProcessingImage::LABEL_LARGE, 500);

        if (null === $thumbnail) {
            $thumbnail = $this->selectAnyValidImage($processingImages);
        }

        return $thumbnail;
    }

    /**
     * @param ProcessingImage[] $processingImages
     * @return string|null
     */
    protected function selectAnyValidImage(array $processingImages)
    {
        foreach ($processingImages as $processingImage) {
            $imageUrl = $processingImage->getUrl();
            $image = $this->buildResponse($imageUrl);
            if ($image->isValid()) {
                return $image->getUrl();
            }
        }

        return null;
    }

    /**
     * @param ProcessingImage[] $processingImages
     * @param $targetLabel
     * @param $targetWidth
     * @return null|string
     */
    protected function selectThumbnail(array $processingImages, $targetLabel, $targetWidth)
    {
        $currentImage = null;
        $currentDifference = 999999;
        foreach ($processingImages as $processingImage) {
            if ($processingImage->getLabel() === $targetLabel) {
                return $processingImage->getUrl();
            }

            $hasBetterWidth = $processingImage->getWidth() && $this->getWidthDifference($processingImage, $targetWidth) < $currentDifference;
            if ($hasBetterWidth) {
                $currentImage = $processingImage;
                $currentDifference = $this->getWidthDifference($processingImage, $targetWidth);
            }
        }

        return null !== $currentImage ? $currentImage->getUrl() : null;
    }

    protected function getWidthDifference(ProcessingImage $processingImage, $desiredWidth)
    {
        return abs($processingImage->getWidth() - $desiredWidth);
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
     * @param Link[] $links
     * @return Link[]
     */
    public function filterToReprocess(array $links)
    {
        $toReprocess = array();
        foreach ($links as $key => $link) {
            if ($this->needsReprocessing($link)) {
                $toReprocess[] = $link;
            }
        }

        return $toReprocess;
    }

    private function needsReprocessing(Link $link)
    {
        $isOld = $this->isImageOld($link);
        $isInvalid = !$this->isValidThumbnail($link);

        return $isOld || $isInvalid;
    }

    private function isImageOld(Link $link)
    {
        $timeToReprocess = 1000 * 3600 * 24 * 7; //1 week in milliseconds

        //we save timestamps in neo4j as milliseconds
        $imageTimestamp = $link->getImageProcessed() ?: 1;
        $nowTimestamp = (new \DateTime())->getTimestamp() * 1000;

        return $imageTimestamp < ($nowTimestamp - $timeToReprocess);
    }

    private function isValidThumbnail(Link $link)
    {
        $thumbnailUrl = $this->getThumbnailFromLink($link);

        return $this->isValidImage($thumbnailUrl);
    }

    //this logic could go in Link->getImageUrl()
    private function getThumbnailFromLink(Link $link)
    {
        $isImage = $link instanceof Image;

        return $isImage ? $link->getUrl() : $link->getThumbnailLarge();
    }

    public function buildResponse($imageUrl)
    {
        if (isset($this->responses[$imageUrl])) {
            return $this->responses[$imageUrl];
        }

        try {
            $config = array(
                'timeout' => 10,
                'connect_timeout' => 10
            );
            $head = $this->client->head($imageUrl, $config);
        } catch (\Exception $e) {
            $head = new Response(404);
        }

        $length = $this->getLength($head, $imageUrl);
        $contentType = isset($head->getHeader('Content-Type')[0]) ? $head->getHeader('Content-Type')[0] : null;

        $response = new ImageResponse($imageUrl, $head->getStatusCode(), $contentType, $length);
        $this->responses[$imageUrl] = $response;

        return $response;
    }

    private function getLength(ResponseInterface $response, $imageUrl)
    {
        $length = !empty($response->getHeader('Content-Length')) ? $response->getHeader('Content-Length')[0] : null;

        $length = $length ?: ($response->getHeader('Accept-Ranges') ? $this->getPartialImage($imageUrl) : null);

        return null !== $length ? $length : ImageResponse::MAX_SIZE + 1;
    }

    //http://stackoverflow.com/questions/2032924/how-to-partially-download-a-remote-file-with-curl
    private function getPartialImage($imageUrl)
    {
        $maxSize = ImageResponse::MAX_SIZE;
        $datadump = null;
        $writefn = function ($ch, $chunk) use ($maxSize, &$datadump) {
            static $data = '';

            $len = strlen($data) + strlen($chunk);
            if ($len >= $maxSize) {
                $data .= substr($chunk, 0, $maxSize - strlen($data));
                $datadump = $data;

                return -1;
            }
            $data .= $chunk;

            return strlen($chunk);
        };

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $imageUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        //curl_setopt($ch, CURLOPT_RANGE, '0-1000'); //not honored by many sites, maybe just remove it altogether.
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, $writefn);
        $data = curl_exec($ch);
        curl_close($ch);

        $length = strlen($datadump);

        return $length < $maxSize ? $length : null;
    }
}