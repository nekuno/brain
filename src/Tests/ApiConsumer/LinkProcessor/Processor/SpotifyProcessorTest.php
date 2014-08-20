<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\SpotifyResourceOwner;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;

class SpotifyProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SpotifyResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var SpotifyUrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('\Http\OAuth\ResourceOwner\SpotifyResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser')
            ->getMock();
        
        $this->processor = new SpotifyProcessor($this->resourceOwner, $this->parser);
    }

    public function testReturnsFalseWhenUrlTypeIsFalse()
    {
        $this->parser            
            ->expects($this->once())
            ->method('getUrlType')
            ->will($this->returnValue(FALSE));

        $this->assertEquals(FALSE, $this->processor->process(array('url' => 'http://www.google.es')), 'Asserting False response from invalid url type');
    }

    /**
     * @param $url
     * @param $type
     * @dataProvider getUrls
     */
    public function testReturnsFalseWhenThereIsNoId($url, $type)
    {
        $this->parser            
            ->expects($this->any())
            ->method('getUrlType')
            ->will($this->returnValue($type));

        $this->parser
            ->expects($this->any())
            ->method('getSpotifyIdFromUrl')
            ->will($this->returnValue(FALSE));

        $this->assertEquals(FALSE, $this->processor->process(array('url' => $url)));
    }

    /**
     * @param $url
     * @param $type
     * @dataProvider getUrls
     */
    public function testDoNotProcessWhenEmptyResponse($url, $type)
    {
        $this->parser            
            ->expects($this->any())
            ->method('getUrlType')
            ->will($this->returnValue($type));

        $this->parser
            ->expects($this->any())
            ->method('getSpotifyIdFromUrl')
            ->will($this->returnValue($url));

        $this->resourceOwner
            ->expects($this->any())
            ->method('authorizedAPIRequest')
            ->will($this->returnValue(array()));

        $link = array('url' => $url);
        $this->assertEquals($link, $this->processor->process($link));
    }

    public function testProcessTrackUrl()
    {
        $this->parser            
            ->expects($this->any())
            ->method('getUrlType')
            ->will($this->returnValue(SpotifyUrlParser::TRACK_URL));

        $this->parser
            ->expects($this->any())
            ->method('getSpotifyIdFromUrl')
            ->will($this->returnValue($this->getTrackId()));

        $this->resourceOwner
            ->expects($this->any())
            ->method('authorizedAPIRequest')
            ->will($this->returnCallback(function ($url, $query) {
                if ($url === 'tracks/'.$this->getTrackId()) {
                    return $this->getTrackResponse();
                }
                if ($url === 'albums/'.$this->getAlbumId()) {
                    return $this->getAlbumResponse();
                }

                return FALSE;
            }));

        $processed = $this->processor->process(array(
            'url' => $this->getTrackUrl(),
        ));

        $this->assertEquals($this->getTrackUrl(), $processed['url']);
        $this->assertEquals('So What', $processed['title']);
        $this->assertEquals('Kind Of Blue (Legacy Edition) : Miles Davis', $processed['description']);
        $this->assertEquals($this->getTrackTags(), $processed['tags']);
    }

    public function testProcessAlbumUrl()
    {
        $this->parser            
            ->expects($this->any())
            ->method('getUrlType')
            ->will($this->returnValue(SpotifyUrlParser::ALBUM_URL));

        $this->parser
            ->expects($this->any())
            ->method('getSpotifyIdFromUrl')
            ->will($this->returnValue($this->getAlbumId()));

        $this->resourceOwner
            ->expects($this->any())
            ->method('authorizedAPIRequest')
            ->will($this->returnValue($this->getAlbumResponse()));

        $processed = $this->processor->process(array(
            'url' => $this->getAlbumUrl(),
        ));

        $this->assertEquals($this->getAlbumUrl(), $processed['url']);
        $this->assertEquals('Kind Of Blue (Legacy Edition)', $processed['title']);
        $this->assertEquals('By: Miles Davis', $processed['description']);
        $this->assertEquals($this->getAlbumTags(), $processed['tags']);
    }

    public function testProcessArtistUrl()
    {
        $this->parser            
            ->expects($this->any())
            ->method('getUrlType')
            ->will($this->returnValue(SpotifyUrlParser::ARTIST_URL));

        $this->parser
            ->expects($this->any())
            ->method('getSpotifyIdFromUrl')
            ->will($this->returnValue($this->getArtistId()));

        $this->resourceOwner
            ->expects($this->any())
            ->method('authorizedAPIRequest')
            ->will($this->returnValue($this->getArtistResponse()));

        $processed = $this->processor->process(array(
            'url' => $this->getArtistUrl(),
        ));

        $this->assertEquals($this->getArtistUrl(), $processed['url']);
        $this->assertEquals('Charlie Parker', $processed['title']);
        $this->assertEquals($this->getArtistTags(), $processed['tags']);
    }

    public function getUrls ()
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

    public function getTrackResponse()
    {
        return array (
            'album' => array(
                'album_type' => 'album',
                'external_urls' => array (
                    'spotify' => 'https://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw',
                ),
                'href' => 'https://api.spotify.com/v1/albums/4sb0eMpDn3upAFfyi4q2rw',
                'id' => '4sb0eMpDn3upAFfyi4q2rw',
                'images' => array (
                    0 => array (
                        'height' => 640,
                        'url' => 'https://i.scdn.co/image/d3a5855bc9c50767090e4e29f2d207061114888d',
                        'width' => 640,
                    ),
                ),
                'name' => 'Kind Of Blue (Legacy Edition)',
                'type' => 'album',
                'uri' => 'spotify:album:4sb0eMpDn3upAFfyi4q2rw',
            ),
            'artists' => array (
                0 => array (
                    'external_urls' => array (
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
            'external_ids' => array (
                'isrc' => 'USSM15900113',
            ),
            'external_urls' => array (
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

    public function getTrackTags ()
    {
        return array(
            0 =>
                array(
                    'name' => 'Jazz',
                    'aditionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            1 =>
                array(
                    'name' => 'Miles Davis',
                    'aditionalLabels' =>
                        array(
                            0 => 'Artist',
                        ),
                    'aditionalFields' =>
                        array(
                            'spotifyId' => '0kbYTNQb4Pb1rPbbaF0pT4',
                        ),
                ),
            2 =>
                array(
                    'name' => 'Kind Of Blue (Legacy Edition)',
                    'aditionalLabels' =>
                        array(
                            0 => 'Album',
                        ),
                    'aditionalFields' =>
                        array(
                            'spotifyId' => '4sb0eMpDn3upAFfyi4q2rw',
                        ),
                ),
            3 =>
                array(
                    'name' => 'So What',
                    'aditionalLabels' =>
                        array(
                            0 => 'Song',
                        ),
                    'aditionalFields' =>
                        array(
                            'spotifyId' => '4vLYewWIvqHfKtJDk8c8tq',
                            'isrc' => 'USSM15900113',
                        ),
                ),
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
        return array (
            'album_type' => 'album',
            'artists' => array (
                0 => array (
                    'external_urls' => array (
                        'spotify' => 'https://open.spotify.com/artist/0kbYTNQb4Pb1rPbbaF0pT4',
                    ),
                    'href' => 'https://api.spotify.com/v1/artists/0kbYTNQb4Pb1rPbbaF0pT4',
                    'id' => '0kbYTNQb4Pb1rPbbaF0pT4',
                    'name' => 'Miles Davis',
                    'type' => 'artist',
                    'uri' => 'spotify:artist:0kbYTNQb4Pb1rPbbaF0pT4',
                ),
            ),
            'external_ids' => array (
                'upc' => '888880696069',
            ),
            'external_urls' => array (
                'spotify' => 'https://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw',
            ),
            'genres' => array (
                0 => 'Jazz',
            ),
            'href' => 'https://api.spotify.com/v1/albums/4sb0eMpDn3upAFfyi4q2rw',
            'id' => '4sb0eMpDn3upAFfyi4q2rw',
            'images' => array (
                0 => array (
                    'height' => 640,
                    'url' => 'https://i.scdn.co/image/d3a5855bc9c50767090e4e29f2d207061114888d',
                    'width' => 640,
                ),
            ),
            'name' => 'Kind Of Blue (Legacy Edition)',
            'popularity' => 71,
            'release_date' => '1959',
            'release_date_precision' => 'year',
            'tracks' => array (),
            'type' => 'album',
            'uri' => 'spotify:album:4sb0eMpDn3upAFfyi4q2rw',
        );
    }

    public function getAlbumTags ()
    {
        return array(
            0 =>
                array(
                    'name' => 'Jazz',
                    'aditionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            1 =>
                array(
                    'name' => 'Miles Davis',
                    'aditionalLabels' =>
                        array(
                            0 => 'Artist',
                        ),
                    'aditionalFields' =>
                        array(
                            'spotifyId' => '0kbYTNQb4Pb1rPbbaF0pT4',
                        ),
                ),
            2 =>
                array(
                    'name' => 'Kind Of Blue (Legacy Edition)',
                    'aditionalLabels' =>
                        array(
                            0 => 'Album',
                        ),
                    'aditionalFields' =>
                        array(
                            'spotifyId' => '4sb0eMpDn3upAFfyi4q2rw',
                        ),
                ),
        );
    }

    public function getArtistId()
    {
        return '4Ww5mwS7BWYjoZTUIrMHfC';
    }

    public function getArtistUrl()
    {
        return 'https://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC';
    }

    public function getArtistResponse()
    {
        return array (
            'external_urls' => array (
                'spotify' => 'https://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC',
            ),
            'genres' => array (
                0 => 'Afro-Cuban',
                1 => 'Afro-Cuban Jazz',
                2 => 'Big Band',
                3 => 'Bop',
            ),
            'href' => 'https://api.spotify.com/v1/artists/4Ww5mwS7BWYjoZTUIrMHfC',
            'id' => '4Ww5mwS7BWYjoZTUIrMHfC',
            'images' => array (
                0 => array (
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

    public function getArtistTags ()
    {
        return array(
            0 =>
                array(
                    'name' => 'Afro-Cuban',
                    'aditionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            1 =>
                array(
                    'name' => 'Afro-Cuban Jazz',
                    'aditionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            2 =>
                array(
                    'name' => 'Big Band',
                    'aditionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            3 =>
                array(
                    'name' => 'Bop',
                    'aditionalLabels' =>
                        array(
                            0 => 'MusicalGenre',
                        ),
                ),
            4 =>
                array(
                    'name' => 'Charlie Parker',
                    'aditionalLabels' =>
                        array(
                            0 => 'Artist',
                        ),
                    'aditionalFields' =>
                        array(
                            'spotifyId' => '4Ww5mwS7BWYjoZTUIrMHfC',
                        ),
                ),
        );
    }
}