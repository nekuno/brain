<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;
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

    /**
     * @param $url
     * @dataProvider getBadUrls
     */
    public function testBadUrlsType($url)
    {
        $this->setExpectedException(UrlNotValidException::class, 'Url ' . $url . ' not valid');
        $this->parser->getUrlType($url);
    }

    /**
     * @param $url
     * @param $expectedType
     * @dataProvider getUrlsForType
     */
    public function testType($url, $expectedType)
    {
        $type = $this->parser->getUrlType($url);
        $this->assertEquals($expectedType, $type, 'Asserting that ' . $url . ' is a track');
    }

    public function testIdIsFalseWhenBadUrlFormat()
    {
        $url = 'This is not an url';
        $this->setExpectedException(UrlNotValidException::class, 'Url ' . $url . ' not valid');
        $this->parser->getUrlType($url);
    }

    /**
     * @param $url
     * @dataProvider getBadUrls
     */
    public function testBadUrlIds($url)
    {
        $this->setExpectedException(UrlNotValidException::class, 'Url ' . $url . ' not valid');
        $this->parser->getUrlType($url);
    }

    /**
     * @param $url
     * @param $id
     * @dataProvider getUrlsForId
     */
    public function testGetIdsFromUrl($url, $id)
    {
        $this->assertEquals($id, $this->parser->getSpotifyId($url), 'Extracting id ' . $id . ' from ' . $url);
    }

    /**
     * @param $url
     * @param $expectedCleanUrl
     * @dataProvider getUrlsForClean
     */
    public function testCleanUrl($url, $expectedCleanUrl){
        $cleanUrl = $this->parser->cleanURL($url);
        $this->assertEquals($expectedCleanUrl, $cleanUrl, 'Cleaning url '. $url);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url'),
            array('http://www.google.es'),
            array('http://open.spotify.com/track/'),
            array('http://open.spotify.com/book/1g9PysFSHeHjVcACqwduNf')
        );
    }

    public function getUrlsForType()
    {
        return array(
            array('http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf', SpotifyUrlParser::TRACK_URL),
            array('https://open.spotify.com/album/02dncVzAWXSAI3e8oJnoug/6BEocXyX92zZ2vLc4yxewo', SpotifyUrlParser::ALBUM_TRACK_URL),
            array('http://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw', SpotifyUrlParser::ALBUM_URL),
            array('http://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC', SpotifyUrlParser::ARTIST_URL)
        );
    }

    public function getUrlsForId()
    {
        return array(
            array('http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf', '1g9PysFSHeHjVcACqwduNf'),
            array('http://open.spotify.com/album/00m9T7kq5EyN6g3gEzgTQN', '00m9T7kq5EyN6g3gEzgTQN'),
            array('http://open.spotify.com/artist/0Y5ldP4uHArYLgHdljfmAu', '0Y5ldP4uHArYLgHdljfmAu'),
            array('https://open.spotify.com/album/677iOjr78hYvgKJjYnCNts/2DYGoXvsFXSwZCoUlkIEDH', '2DYGoXvsFXSwZCoUlkIEDH'),
        );
    }

    public function getUrlsForClean()
    {
        return array(
            array('https://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf', 'https://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf'),
            array('http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf/', 'https://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf'),
            array('https://open.spotify.com/album/677iOjr78hYvgKJjYnCNts/2DYGoXvsFXSwZCoUlkIEDH', 'https://open.spotify.com/track/2DYGoXvsFXSwZCoUlkIEDH')
        );
    }
}