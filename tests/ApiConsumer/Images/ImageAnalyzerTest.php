<?php

namespace Tests\ApiConsumer\Images;

use ApiConsumer\Images\ImageAnalyzer;
use ApiConsumer\Images\ProcessingImage;
use GuzzleHttp\Psr7\Response;
use Model\Link\Image;
use Model\Link\Link;
use PHPUnit\Framework\TestCase;

class ImageAnalyzerTest extends TestCase
{
    /**
     * @dataProvider getImages
     */
    public function testSelectImage($imageData, $expectedSelected, $message)
    {
        $imageUrls = array();
        foreach ($imageData as $url => $response) {
            $imageUrls[] = new ProcessingImage($url);
        }

        $client = $this->getMockBuilder('GuzzleHttp\Client')
            ->setMethods(['head'])
            ->getMock();
        $client
            ->expects($this->atLeastOnce())
            ->method('head')
            ->will(
                $this->returnCallback(
                    function ($url) use ($imageData) {
                        return $imageData[$url];
                    }
                )
            );
        $imageAnalyzer = new ImageAnalyzer($client);

        $selected = $imageAnalyzer->selectLargeThumbnail($imageUrls);

        $this->assertEquals($expectedSelected, $selected, $message);
    }

    public function getImages()
    {
        return array(
            array(
                $this->getBigNotValidImageResponse() + $this->getSmallValidImageResponse(),
                array_keys($this->getSmallValidImageResponse())[0],
                'Detecting a too big image when without content-length header',
            ),
            array(
                $this->getSmallValidImageResponse() + $this->getSmallRecommendedImageResponse(),
                array_keys($this->getSmallValidImageResponse())[0],
                'Choosing recommended image over a valid one',
            ),
            array(
                $this->getSmallNotValidImage() + $this->getBigNotValidImageResponse(),
                null,
                'Returning null if too small or too big images are provided'
            ),
            array(
                $this->getBigValidImageResponse(),
                array_keys($this->getBigValidImageResponse())[0],
                'Detecting a valid image too big to be recommended'
            ),
            array(
                $this->getSmallValidImageResponse() + $this->getBigValidImageResponse(),
                array_keys($this->getSmallValidImageResponse())[0],
                'Choosing smallest valid image first',
            )
        );
    }

    /**
     * @dataProvider getLinks
     */
    public function testFilterToReprocess(array $links, $responses, $expectedLinks, $message)
    {
        $client = $this->getMockBuilder('GuzzleHttp\Client')
            ->setMethods(['head'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('head')
            ->will(
                $this->returnCallback(
                    function ($url) use ($responses) {
                        return $responses[$url];
                    }
                )
            );

        $imageAnalyzer = new ImageAnalyzer($client);
        $filteredLinks = $imageAnalyzer->filterToReprocess($links);

        $this->assertEquals($expectedLinks, $filteredLinks, $message);
    }

    public function getLinks()
    {
        $smallLink = new Link();
        $smallLink->setImageProcessed(0);
        $smallLink->setThumbnail($this->getSmallRecommendedUrl());

        $bigNotValidLink = new Link();
        $bigNotValidLink->setImageProcessed(2521843200000);
        $bigNotValidLink->setThumbnail($this->getBigNotValidUrl());

        $bigNotValidImage = new Image();
        $bigNotValidImage->setUrl($this->getBigNotValidUrl());
        $bigNotValidImage->setImageProcessed(2521843200000);

        $smallLateLink = new Link();
        $smallLateLink->setImageProcessed(2521843200000);
        $smallLateLink->setThumbnail($this->getSmallValidUrl());

        return array(
            array(
                array(
                    $smallLink,
                    $smallLateLink,
                    $bigNotValidLink,
                    $bigNotValidImage,
                ),
                $this->getSmallRecommendedImageResponse() + $this->getSmallValidImageResponse() + $this->getBigNotValidImageResponse(),
                array(
                    $smallLink,
                    $bigNotValidLink,
                    $bigNotValidImage,
                ),
                'Needs reprocessing if old or not valid'
            ),
        );
    }

    private function getSmallNotValidImage()
    {
        return array($this->getSmallNotValidUrl() => new Response(200, array('Content-Type' => 'image/jpeg', 'Content-Length' => 993)));
    }

    private function getSmallNotValidUrl()
    {
        return 'http://wfarm1.dataknet.com/static/resources/icons/set28/7f8535d7.png';
    }

    private function getSmallValidImageResponse()
    {
        return array($this->getSmallValidUrl() => new Response(200, array('Content-Type' => 'image/jpeg', 'Content-Length' => 7538)));
    }

    private function getSmallValidUrl()
    {
        return 'https://pbs.twimg.com/profile_images/623488788409548800/IGqhhJs7.jpg';
    }

    private function getSmallRecommendedImageResponse()
    {
        return array($this->getSmallRecommendedUrl() => new Response(200, array('Content-Length' => 91179, 'Content-Type' => 'image/jpeg')),);
    }

    private function getSmallRecommendedUrl()
    {
        return 'https://c24e867c169a525707e0-bfbd62e61283d807ee2359a795242ecb.ssl.cf3.rackcdn.com/imagenes/gato/etapas-clave-de-su-vida/gatitos/nuevo-gatito-en-casa/gatito-durmiendo-en-cama.jpg';
    }

    private function getBigValidImageResponse()
    {
        return array($this->getBigValidUrl() => new Response(200, array('Content-Type' => 'image/jpeg', 'Content-Length' => 726946)));
    }

    private function getBigValidUrl()
    {
        return 'http://www.nato.int/pictures/2004/040626b/b040626v.jpg';
    }

    private function getBigNotValidImageResponse()
    {
        return array($this->getBigNotValidUrl() => new Response(200, array('Content-Type' => 'image/jpeg', 'Accept-Ranges' => 'bytes')),);
    }

    private function getBigNotValidUrl()
    {
        return 'http://files.elver.webnode.es/200000035-3c3da3d378/GATO.JPG';
    }
}