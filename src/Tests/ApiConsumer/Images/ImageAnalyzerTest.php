<?php

namespace Tests\ApiConsumer\Images;

use ApiConsumer\Images\ImageAnalyzer;
use GuzzleHttp\Message\Response;

class ImageAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getImages
     */
    public function testSelectImage($imageData, $expectedSelected)
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

        $this->assertEquals($expectedSelected, $selected, 'Selecting image from array of urls');
    }

    public function getImages()
    {
        return array(
            array(
                array(
                    'http://files.elver.webnode.es/200000035-3c3da3d378/GATO.JPG' => new Response(200, array('Content-Type' => 'image/jpeg', 'Accept-Ranges' => 'bytes')),
                    'https://c24e867c169a525707e0-bfbd62e61283d807ee2359a795242ecb.ssl.cf3.rackcdn.com/imagenes/gato/etapas-clave-de-su-vida/gatitos/nuevo-gatito-en-casa/gatito-durmiendo-en-cama.jpg' => new Response(200, array('Content-Length' => 91179, 'Content-Type' => 'image/jpeg')),

                ),
                'https://c24e867c169a525707e0-bfbd62e61283d807ee2359a795242ecb.ssl.cf3.rackcdn.com/imagenes/gato/etapas-clave-de-su-vida/gatitos/nuevo-gatito-en-casa/gatito-durmiendo-en-cama.jpg'
            )
        );
    }
}