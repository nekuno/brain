<?php

namespace Tests\ApiConsumer\Images;

use ApiConsumer\Images\ImageAnalyzer;
use GuzzleHttp\Message\Response;
use Model\Link;

class ImageAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getImages
     */
    public function testSelectImage($imageData, $expectedSelected, $message)
    {
        $imageUrls = array();
        foreach ($imageData as $url => $response) {
            $imageUrls[] = $url;
        }

        $client = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $client
            ->expects($this->exactly(count($imageData)))
            ->method('head')
            ->will(
                $this->returnCallback(
                    function ($url) use ($imageData) {
                        return $imageData[$url];
                    }
                )
            );
        $imageAnalyzer = new ImageAnalyzer($client);

        $selected = $imageAnalyzer->selectImage($imageUrls);

        $this->assertEquals($expectedSelected, $selected, $message);
    }

    public function getImages()
    {
        return array(
            array(
                $this->getBigNotValidImage() + $this->getSmallValidImage(),
                array_keys($this->getSmallValidImage())[0],
                'Detecting a too big image when without content-length header',
            ),
            array(
                $this->getSmallValidImage() + $this->getSmallRecommendedImage(),
                array_keys($this->getSmallRecommendedImage())[0],
                'Choosing recommended image over a valid one',
            ),
            array(
                $this->getSmallNotValidImage() + $this->getBigNotValidImage(),
                null,
                'Returning null if too small or too big images are provided'
            ),
            array(
                $this->getBigValidImage(),
                array_keys($this->getBigValidImage())[0],
                'Detecting a valid image too big to be recommended'
            ),
            array(
                $this->getSmallValidImage() + $this->getBigValidImage(),
                array_keys($this->getSmallValidImage())[0],
                'Choosing smallest valid image first',
            )
        );
    }

    /**
     * @dataProvider getLinks
     */
    public function testFilterToReprocess(array $links, $responses, $expectedLinks, $message)
    {
        $client = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
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
        return array(
            array(
                array(
                    array('imageProcessed' => 0, 'thumbnail' => $this->getSmallRecommendedUrl()),
                    array('imageProcessed' => 2521843200000, 'thumbnail' => $this->getSmallValidUrl()),
                    array('imageProcessed' => 2521843200000, 'thumbnail' => $this->getBigNotValidUrl()),
                    array('imageProcessed' => 2521843200000, 'additionalLabels' => array('Image'), 'url' => $this->getBigNotValidUrl())
                ),
                $this->getSmallRecommendedImage() + $this->getSmallValidImage() + $this->getBigNotValidImage(),
                array(
                    array('imageProcessed' => 0, 'thumbnail' => $this->getSmallRecommendedUrl()),
                    array('imageProcessed' => 2521843200000, 'thumbnail' => $this->getBigNotValidUrl()),
                    array('imageProcessed' => 2521843200000, 'additionalLabels' => array('Image'), 'url' => $this->getBigNotValidUrl())
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

    private function getSmallValidImage()
    {
        return array($this->getSmallValidUrl() => new Response(200, array('Content-Type' => 'image/jpeg', 'Content-Length' => 7538)));
    }

    private function getSmallValidUrl()
    {
        return 'https://pbs.twimg.com/profile_images/623488788409548800/IGqhhJs7.jpg';
    }

    private function getSmallRecommendedImage()
    {
        return array($this->getSmallRecommendedUrl() => new Response(200, array('Content-Length' => 91179, 'Content-Type' => 'image/jpeg')),);
    }

    private function getSmallRecommendedUrl()
    {
        return 'https://c24e867c169a525707e0-bfbd62e61283d807ee2359a795242ecb.ssl.cf3.rackcdn.com/imagenes/gato/etapas-clave-de-su-vida/gatitos/nuevo-gatito-en-casa/gatito-durmiendo-en-cama.jpg';
    }

    private function getBigValidImage()
    {
        return array($this->getBigValidUrl() => new Response(200, array('Content-Type' => 'image/jpeg', 'Content-Length' => 726946)));
    }

    private function getBigValidUrl()
    {
        return 'http://www.nato.int/pictures/2004/040626b/b040626v.jpg';
    }

    private function getBigNotValidImage()
    {
        return array($this->getBigNotValidUrl() => new Response(200, array('Content-Type' => 'image/jpeg', 'Accept-Ranges' => 'bytes')),);
    }

    private function getBigNotValidUrl()
    {
        return 'http://files.elver.webnode.es/200000035-3c3da3d378/GATO.JPG';
    }
}