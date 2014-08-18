<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;

class SpotifyUrlParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SpotifyUrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new SpotifyUrlParser();
    }

    public function testTypeIsFalseWhenUrlHasNoPath()
    {
        $url = 'http://www.google.es';
        $this->assertEquals(FALSE, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid');
    }

    public function testTypeIsFalseWhenUrlIsNotAValidType()
    {
        $url = 'http://open.spotify.com/book/1g9PysFSHeHjVcACqwduNf';
        $this->assertEquals(FALSE, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid');
    }

    public function testTypeIsTrack()
    {
        $url = 'http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf';
        $type = SpotifyUrlParser::TRACK_URL;

        $this->assertEquals($type, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is a track');
    }

    public function testTypeIsAlbum()
    {
        $url = 'http://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw';
        $type = SpotifyUrlParser::ALBUM_URL;

        $this->assertEquals($type, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is an album');
    }

    public function testTypeIsArtist()
    {
        $url = 'http://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC';
        $type = SpotifyUrlParser::ARTIST_URL;

        $this->assertEquals($type, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is an artist');
    }

    /**
     * @param $url
     * @param $id
     * @dataProvider getUrlsForId
     */
    public function testGetIdsFromUrl($url, $id)
    {
        $this->assertEquals($id, $this->parser->getSpotifyIdFromUrl($url), 'Extracting id ' . $id . ' from ' . $url);
    }

    /**
     * @return array
     */
    public function getUrlsForId()
    {
        return array(
            array('http://www.google.es', false),
            array('http://open.spotify.com/track/', false),
            array('http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf', '1g9PysFSHeHjVcACqwduNf'),
            array('http://open.spotify.com/album/00m9T7kq5EyN6g3gEzgTQN', '00m9T7kq5EyN6g3gEzgTQN'),
            array('http://open.spotify.com/artist/0Y5ldP4uHArYLgHdljfmAu', '0Y5ldP4uHArYLgHdljfmAu'),
        );
    }
}