<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;

class UrlParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var UrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new UrlParser();
    }

    /**
     * @param $url
     * @dataProvider getBadUrls
     */
    public function testCheckUrls($url)
    {
        $this->setExpectedException(UrlNotValidException::class, 'Url ' . $url . ' not valid');
        $this->parser->checkUrlValid($url);
    }

    /**
     * @dataProvider getUrlsForType
     */
    public function testType($url, $expectedType)
    {
        $type = $this->parser->getUrlType($url);
        $this->assertEquals($expectedType, $type, 'Asserting that ' . $url . ' is a ' . $expectedType);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testTypeBad($url)
    {
        $this->setExpectedException(UrlNotValidException::class, 'Url ' . $url . ' not valid');
        $this->parser->getUrlType($url);
    }

    /**
     * @dataProvider getTextForUrls
     */
    public function testExtractUrls($text, $expectedUrls)
    {
        $urls = $this->parser->extractURLsFromText($text);
        $this->assertEquals($expectedUrls, $urls, 'Asserting that urls from ' . $text . ' are ' . json_encode($urls));
    }

    /**
     * @dataProvider getUrlsForClean
     */
    public function testCleanUrl($url, $expectedCleanUrl)
    {
        $cleanUrl = $this->parser->cleanURL($url);
        $this->assertEquals($expectedCleanUrl, $cleanUrl, 'Asserting that clean url from ' . $url . ' is ' . $cleanUrl);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testCleanUrlBad($url)
    {
        $this->setExpectedException(UrlNotValidException::class, 'Url ' . $url . ' not valid');
        $this->parser->cleanURL($url);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url'),
        );
    }

    public function getUrlsForType()
    {
        return array(
            array('http://www.facebook.com/vips', UrlParser::SCRAPPER),
            array('https://www.twitter.com/nekuno', UrlParser::SCRAPPER),
        );
    }

    public function getTextForUrls()
    {
        return array(
            array('texto http://www.google.com/', array('http://www.google.com/')),
            array('texto http://www.google.com más texto https://www.twitter.com text', array('http://www.google.com', 'https://www.twitter.com')),
        );
    }

    public function getUrlsForClean()
    {
        return array(
            array('http://www.google.com/', 'http://www.google.com'),
            array('http://facebook.com?', 'http://facebook.com'),
        );
    }

}