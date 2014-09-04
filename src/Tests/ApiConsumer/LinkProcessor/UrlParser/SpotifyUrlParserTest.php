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

    public function testTypeIsFalseWhenBadUrlFormat()
    {
        $url = 'This is not an url';
        $this->assertEquals(false, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid format');
    }

    public function testTypeIsFalseWhenUrlHasNoPath()
    {
        $url = 'http://www.google.es';
        $this->assertEquals(false, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid');
    }

    public function testTypeIsFalseWhenUrlIsNotAValidType()
    {
        $url = 'http://open.spotify.com/book/1g9PysFSHeHjVcACqwduNf';
        $this->assertEquals(false, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid');
    }

    public function testTypeIsTrack()
    {
        $url = 'http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf';
        $type = SpotifyUrlParser::TRACK_URL;

        $this->assertEquals($type, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is a track');
    }

    public function testTypeIsTrackWithAlbumUrl()
    {
        $url = 'https://open.spotify.com/album/02dncVzAWXSAI3e8oJnoug/6BEocXyX92zZ2vLc4yxewo';
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

    public function testIdIsFalseWhenBadUrlFormat()
    {
        $url = 'This is not an url';
        $this->assertEquals(false, $this->parser->getSpotifyIdFromUrl($url), 'Asserting that ' . $url . ' is not valid format');
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
            array('https://open.spotify.com/album/677iOjr78hYvgKJjYnCNts/2DYGoXvsFXSwZCoUlkIEDH', '2DYGoXvsFXSwZCoUlkIEDH'),
        );
    }
}