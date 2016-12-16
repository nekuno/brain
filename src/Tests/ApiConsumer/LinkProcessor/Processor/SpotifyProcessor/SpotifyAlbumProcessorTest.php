<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor\SpotifyAlbumProcessor;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use ApiConsumer\ResourceOwner\SpotifyResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;

class SpotifyAlbumProcessorTest extends \PHPUnit_Framework_TestCase
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
     * @var SpotifyAlbumProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\SpotifyResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser')
            ->getMock();

        $this->processor = new SpotifyAlbumProcessor($this->resourceOwner, $this->parser);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->setExpectedException('ApiConsumer\Exception\CannotProcessException', 'Could not process url ' . $url);

        $this->parser->expects($this->once())
            ->method('getSpotifyId')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $this->processor->requestItem($link);
    }

    /**
     * @dataProvider getAlbumForRequestItem
     */
    public function testRequestItem($url, $id, $album)
    {
        $this->parser->expects($this->once())
            ->method('getSpotifyId')
            ->will($this->returnValue($id));

        $this->resourceOwner->expects($this->once())
            ->method('requestAlbum')
            ->will($this->returnValue($album));

        $link = new PreprocessedLink($url);
        $response = $this->processor->requestItem($link);

        $this->assertEquals($response, $album);
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

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getUrls()
    {
        return array(
            array($this->getTrackUrl(), SpotifyUrlParser::TRACK_URL),
            array($this->getAlbumUrl(), SpotifyUrlParser::ALBUM_URL),
            array($this->getArtistUrl(), SpotifyUrlParser::ARTIST_URL),
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

    public function getAlbumForRequestItem()
    {
        return array(
            array(
                $this->getAlbumUrl(),
                $this->getAlbumId(),
                $this->getAlbumResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        return array(
            array(
                $this->getAlbumUrl(),
                $this->getAlbumResponse(),
                array(
                    'title' => 'Kind Of Blue (Legacy Edition)',
                    'description' => 'By: Miles Davis',
                    'thumbnail' => null,
                    'url' => null,
                    'id' => null,
                    'tags' => array(),
                    'created' => null,
                    'processed' => true,
                    'language' => null,
                    'synonymous' => array(),
                    'imageProcessed' => null,
                    'embed_type' => 'spotify',
                    'embed_id' => 'spotify:album:4sb0eMpDn3upAFfyi4q2rw',
                )
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getAlbumUrl(),
                $this->getAlbumResponse(),
                $this->getAlbumTags(),
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

    public function getAlbumResponseIncomplete()
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

    public function getAlbumId()
    {
        return '4sb0eMpDn3upAFfyi4q2rw';
    }

    public function getAlbumUrl()
    {
        return 'http://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw';
    }

    public function getAlbumResponse()
    {
        return array(
            'album_type' => 'album',
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
            'external_ids' => array(
                'upc' => '888880696069',
            ),
            'external_urls' => array(
                'spotify' => 'https://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw',
            ),
            'genres' => array(
                0 => 'Jazz',
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
            'popularity' => 71,
            'release_date' => '1959',
            'release_date_precision' => 'year',
            'tracks' => array(),
            'type' => 'album',
            'uri' => 'spotify:album:4sb0eMpDn3upAFfyi4q2rw',
        );
    }

    public function getAlbumTags()
    {
        return array(
            0 =>
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
            1 =>
                array(
                    'name' => 'Jazz',
                    'additionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
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

    public function getArtistUrl()
    {
        return 'https://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC';
    }

}