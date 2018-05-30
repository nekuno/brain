<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor\AbstractSpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor\SpotifyArtistProcessor;
use ApiConsumer\ResourceOwner\SpotifyResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use Model\Link\Link;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class SpotifyArtistProcessorTest extends AbstractProcessorTest
{
    /**
     * @var SpotifyResourceOwner|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOwner;

    /**
     * @var SpotifyUrlParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var SpotifyArtistProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\SpotifyResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser')
            ->getMock();

        $this->processor = new SpotifyArtistProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . SpotifyUrlParser::DEFAULT_IMAGE_PATH);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->expectException(CannotProcessException::class);

        $this->parser->expects($this->once())
            ->method('getSpotifyId')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getArtistForRequestItem
     */
    public function testRequestItem($url, $id, $artist)
    {
        $this->parser->expects($this->once())
            ->method('getSpotifyId')
            ->will($this->returnValue($id));

        $this->resourceOwner->expects($this->once())
            ->method('requestArtist')
            ->will($this->returnValue($artist));

        $link = new PreprocessedLink($url);
        $response = $this->processor->getResponse($link);

        $this->assertEquals($artist, $response, 'Asserting correct artist response for ' . $url);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $response, $expectedArray)
    {
        $link = new PreprocessedLink($url);
        $this->processor->hydrateLink($link, $response);

        $this->assertEquals($expectedArray, $link->getFirstLink()->toArray(), 'Asserting correct hydrated link for ' . $url);
    }

    /**
     * @dataProvider getResponseTags
     */
    public function testAddTags($url, $response, $expectedTags)
    {
        $link = new PreprocessedLink($url);
        $this->processor->addTags($link, $response);

        $tags = $expectedTags;
        sort($tags);
        $resultTags = $link->getFirstLink()->getTags();
        sort($resultTags);
        $this->assertEquals($tags, $resultTags);
    }

    /**
     * @dataProvider getResponseImages
     */
    public function testGetImages($url, $response, $expectedImages)
    {
        $link = new PreprocessedLink($url);
        $images = $this->processor->getImages($link, $response);

        $this->assertEquals($expectedImages, $images, 'Images gotten from response');
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getArtistForRequestItem()
    {
        return array(
            array(
                $this->getArtistUrl(),
                $this->getArtistId(),
                $this->getArtistResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expected = new Link();
        $expected->setTitle('Charlie Parker');
        $expected->addAdditionalLabels(AbstractSpotifyProcessor::SPOTIFY_LABEL);

        return array(
            array(
                $this->getArtistUrl(),
                $this->getArtistResponse(),
                $expected->toArray(),
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getArtistUrl(),
                $this->getArtistResponse(),
                $this->getArtistTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getArtistUrl(),
                $this->getArtistResponse(),
                $this->getProcessingImages()
            )
        );
    }

    public function getArtistId()
    {
        return '4Ww5mwS7BWYjoZTUIrMHfC';
    }
//
    public function getArtistUrl()
    {
        return 'https://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC';
    }

    public function getArtistResponse()
    {
        return array(
            'external_urls' => array(
                'spotify' => 'https://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC',
            ),
            'genres' => array(
                0 => 'Afro-Cuban',
                1 => 'Afro-Cuban Jazz',
                2 => 'Big Band',
                3 => 'Bop',
            ),
            'href' => 'https://api.spotify.com/v1/artists/4Ww5mwS7BWYjoZTUIrMHfC',
            'id' => '4Ww5mwS7BWYjoZTUIrMHfC',
            'images' => array(
                0 => array(
                    'height' => 1198,
                    'url' => 'https://i.scdn.co/image/e2bd9ef3de6d7fa43ed877388249c6415e76a9c4',
                    'width' => 1000,
                ),
            ),
            'name' => 'Charlie Parker',
            'popularity' => 68,
            'type' => 'artist',
            'uri' => 'spotify:artist:4Ww5mwS7BWYjoZTUIrMHfC',
        );
    }

    public function getArtistTags()
    {
        return array(
            0 =>
                array(
                    'name' => 'Charlie Parker',
                    'additionalLabels' =>
                        array(
                            0 => 'Artist',
                        ),
                    'additionalFields' =>
                        array(
                            'spotifyId' => '4Ww5mwS7BWYjoZTUIrMHfC',
                        ),
                ),
            1 =>
                array(
                    'name' => 'Afro-Cuban',
                    'additionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            2 =>
                array(
                    'name' => 'Afro-Cuban Jazz',
                    'additionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            3 =>
                array(
                    'name' => 'Big Band',
                    'additionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            4 =>
                array(
                    'name' => 'Bop',
                    'additionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
        );
    }

    public function getProcessingImages()
    {
        $processingImage = new ProcessingImage('https://i.scdn.co/image/e2bd9ef3de6d7fa43ed877388249c6415e76a9c4');
        $processingImage->setWidth(1000);
        $processingImage->setHeight(1198);
        return array ($processingImage);
    }
}