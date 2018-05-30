<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor\AbstractSpotifyProcessor;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use ApiConsumer\ResourceOwner\SpotifyResourceOwner;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor\SpotifyTrackProcessor;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use Model\Link\Audio;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class SpotifyTrackProcessorTest extends AbstractProcessorTest
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
     * @var SpotifyTrackProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\SpotifyResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser')
            ->getMock();

        $this->processor = new SpotifyTrackProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . SpotifyUrlParser::DEFAULT_IMAGE_PATH);
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
     * @dataProvider getTrackResponseIncomplete
     */
    public function testBadResponseRequestItem($url, $id, $response)
    {
        $this->expectException(CannotProcessException::class);

        $this->parser->expects($this->once())
            ->method('getSpotifyId')
            ->will($this->returnValue($id));

        $this->resourceOwner->expects($this->once())
            ->method('requestTrack')
            ->will($this->returnValue($response));

        $link = new PreprocessedLink($url);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getTrackForRequestItem
     */
    public function testRequestItem($url, $id, $track)
    {
        $this->parser->expects($this->once())
            ->method('getSpotifyId')
            ->will($this->returnValue($id));

        $this->resourceOwner->expects($this->once())
            ->method('requestTrack')
            ->will($this->returnValue($track));

        $link = new PreprocessedLink($url);
        $response = $this->processor->getResponse($link);

        $this->assertEquals($track, $response, 'Asserting correct track response for ' . $url);
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

    /**
     * @dataProvider getResponseSynonymous
     */
    public function testSynonymousParameters($url, $response, $expectedParameters)
    {
        $link = new PreprocessedLink($url);
        $this->processor->getSynonymousParameters($link, $response);

        $this->assertEquals($expectedParameters, $link->getSynonymousParameters(), 'Asserting track synonymous parameters');
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getTrackId()
    {
        return '4vLYewWIvqHfKtJDk8c8tq';
    }

    public function getTrackUrl()
    {
        return 'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq';
    }

    public function getTrackForRequestItem()
    {
        return array(
            array(
                'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq',
                '4vLYewWIvqHfKtJDk8c8tq',
                $this->getTrackResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expected = new Audio();
        $expected->setTitle('So What');
        $expected->setDescription('Kind Of Blue (Legacy Edition) : Miles Davis');
        $expected->setEmbedType('spotify');
        $expected->setEmbedId('spotify:track:4vLYewWIvqHfKtJDk8c8tq');
        $expected->addAdditionalLabels(AbstractSpotifyProcessor::SPOTIFY_LABEL);

        return array(
            array(
                'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq',
                $this->getTrackResponse(),
                $expected->toArray()
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq',
                $this->getTrackResponse(),
                $this->getTrackTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getTrackUrl(),
                $this->getTrackResponse(),
                $this->getProcessingImages()
            )
        );
    }

    public function getResponseSynonymous()
    {
        $parameters = new SynonymousParameters();
        $parameters->setQuantity(3);
        $parameters->setQuery('Miles Davis So What');
        $parameters->setType(YoutubeUrlParser::VIDEO_URL);

        return array(
            array(
                'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq',
                $this->getTrackResponse(),
                $parameters,
            )
        );
    }

    public function getTrackResponse()
    {
        return array(
            'album' => array(
                'album_type' => 'album',
                'external_urls' => array(
                    'spotify' => 'https://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw',
                ),
                'href' => 'https://api.spotify.com/v1/albums/4sb0eMpDn3upAFfyi4q2rw',
                'id' => '4sb0eMpDn3upAFfyi4q2rw',
                'images' => array(
                    0 => array(
                        'height' => 640,
                        'url' => 'https://i.scdn.co/image/d3a5855bc9c50767090e4e29f2d207061114888d',
                        'width' => 640,
                    ),
                ),
                'name' => 'Kind Of Blue (Legacy Edition)',
                'type' => 'album',
                'uri' => 'spotify:album:4sb0eMpDn3upAFfyi4q2rw',
            ),
            'artists' => array(
                0 => array(
                    'external_urls' => array(
                        'spotify' => 'https://open.spotify.com/artist/0kbYTNQb4Pb1rPbbaF0pT4',
                    ),
                    'href' => 'https://api.spotify.com/v1/artists/0kbYTNQb4Pb1rPbbaF0pT4',
                    'id' => '0kbYTNQb4Pb1rPbbaF0pT4',
                    'name' => 'Miles Davis',
                    'type' => 'artist',
                    'uri' => 'spotify:artist:0kbYTNQb4Pb1rPbbaF0pT4',
                ),
            ),
            'disc_number' => 1,
            'duration_ms' => 562640,
            'explicit' => false,
            'external_ids' => array(
                'isrc' => 'USSM15900113',
            ),
            'external_urls' => array(
                'spotify' => 'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq',
            ),
            'href' => 'https://api.spotify.com/v1/tracks/4vLYewWIvqHfKtJDk8c8tq',
            'id' => '4vLYewWIvqHfKtJDk8c8tq',
            'name' => 'So What',
            'popularity' => 65,

            'preview_url' => 'https://p.scdn.co/mp3-preview/607c30df64cc38ae96876a5c2822dac07a570992',
            'track_number' => 1,
            'type' => 'track',
            'uri' => 'spotify:track:4vLYewWIvqHfKtJDk8c8tq',
        );
    }

    public function getTrackResponseIncomplete()
    {
        return array(
            array(
                'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq',
                '4vLYewWIvqHfKtJDk8c8tq',
                array(
                    'artists' => array(
                        0 => array(
                            'external_urls' => array(
                                'spotify' => 'https://open.spotify.com/artist/0kbYTNQb4Pb1rPbbaF0pT4',
                            ),
                            'href' => 'https://api.spotify.com/v1/artists/0kbYTNQb4Pb1rPbbaF0pT4',
                            'id' => '0kbYTNQb4Pb1rPbbaF0pT4',
                            'name' => 'Miles Davis',
                            'type' => 'artist',
                            'uri' => 'spotify:artist:0kbYTNQb4Pb1rPbbaF0pT4',
                        ),
                    ),
                    'disc_number' => 1,
                    'duration_ms' => 562640,
                    'explicit' => false,
                    'external_ids' => array(
                        'isrc' => 'USSM15900113',
                    ),
                    'external_urls' => array(
                        'spotify' => 'https://open.spotify.com/track/4vLYewWIvqHfKtJDk8c8tq',
                    ),
                    'href' => 'https://api.spotify.com/v1/tracks/4vLYewWIvqHfKtJDk8c8tq',
                    'id' => '4vLYewWIvqHfKtJDk8c8tq',
                    'name' => 'So What',
                    'popularity' => 65,

                    'preview_url' => 'https://p.scdn.co/mp3-preview/607c30df64cc38ae96876a5c2822dac07a570992',
                    'track_number' => 1,
                    'type' => 'track',
                    'uri' => 'spotify:track:4vLYewWIvqHfKtJDk8c8tq',
                )
            )
        );
    }

    public function getTrackTags()
    {
        return array(
            0 =>
                array(
                    'name' => 'So What',
                    'additionalLabels' =>
                        array(
                            0 => 'Song',
                        ),
                    'additionalFields' =>
                        array(
                            'spotifyId' => '4vLYewWIvqHfKtJDk8c8tq',
                            'isrc' => 'USSM15900113',
                        ),
                ),
            1 =>
                array(
                    'name' => 'Kind Of Blue (Legacy Edition)',
                    'additionalLabels' =>
                        array(
                            0 => 'Album',
                        ),
                    'additionalFields' =>
                        array(
                            'spotifyId' => '4sb0eMpDn3upAFfyi4q2rw',
                        ),
                ),
//            2 =>
//                array(
//                    'name' => 'Jazz',
//                    'additionalLabels' =>
//                        array(
//                            0 => 'MusicalGenre',
//                        ),
//                ),
            2 =>
                array(
                    'name' => 'Miles Davis',
                    'additionalLabels' =>
                        array(
                            0 => 'Artist',
                        ),
                    'additionalFields' =>
                        array(
                            'spotifyId' => '0kbYTNQb4Pb1rPbbaF0pT4',
                        ),
                ),
        );
    }

    public function getProcessingImages()
    {
        $processingImage = new ProcessingImage('https://i.scdn.co/image/d3a5855bc9c50767090e4e29f2d207061114888d');
        $processingImage->setWidth(640);
        $processingImage->setHeight(640);
        return array ($processingImage);
    }
}