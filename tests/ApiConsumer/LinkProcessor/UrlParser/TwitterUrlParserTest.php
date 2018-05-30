<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use PHPUnit\Framework\TestCase;

class TwitterUrlParserTest extends TestCase
{

    /**
     * @var TwitterUrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new TwitterUrlParser();
    }

    /**
     * @param $url
     * @dataProvider getBadUrls
     */
    public function testBadUrls($url)
    {
        $this->expectException(UrlNotValidException::class);
        $url = $this->parser->cleanURL($url);
        $this->parser->getUrlType($url);
    }

    /**
     * @param $url
     * @param $expectedType
     * @dataProvider getUrlsForType
     */
    public function testType($url, $expectedType)
    {
        $url = $this->parser->cleanURL($url);
        $type = $this->parser->getUrlType($url);
        $this->assertEquals($expectedType, $type, 'Asserting that ' . $url . ' is a ' .$expectedType);
    }

    /**
     * @param $url
     * @param $expectedId
     * @dataProvider getUrlsForProfileId
     */
    public function testProfileIds($url, $expectedId)
    {
        $url = $this->parser->cleanURL($url);
        $id = $this->parser->getProfileId($url);
        $this->assertEquals($expectedId, $id, 'Asserting that ' . $url . ' has profile id ' . json_encode($id));
    }

    /**
     * @param $text
     * @param $expectedUrls
     * @dataProvider getTextForUrls
     */
    public function testExtractUrls($text, $expectedUrls){
        $urls = $this->parser->extractURLsFromText($text);
        $this->assertEquals($expectedUrls, $urls, 'Asserting that text "'.$text.'" includes urls '.json_encode($expectedUrls));
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url'),
            array('http://www.google.es'),
            array('http://twitter.com/tweet/not'),
            array('http://www.twitter.com/tweet/not'),
        );
    }

    public function getUrlsForType()
    {
        return array(
            array('http://pic.twitter.com/TdlkpWjYie', TwitterUrlParser::TWITTER_PIC),
            array('https://twitter.com/BigDataBlogs/status/780714415670849536?lang=es', TwitterUrlParser::TWITTER_TWEET),
            array('https://twitter.com/BigDataBlogs', TwitterUrlParser::TWITTER_PROFILE),
            array('https://www.twitter.com/BigDataBlogs', TwitterUrlParser::TWITTER_PROFILE),
            array('http://www.twitter.com/BigDataBlogs', TwitterUrlParser::TWITTER_PROFILE),
            array('https://twitter.com/intent/user?screen_name=NASA', TwitterUrlParser::TWITTER_INTENT),
        );
    }

    public function getUrlsForProfileId()
    {
        return array(
            array('https://twitter.com/BigDataBlogs', array('screen_name' => 'BigDataBlogs')),
            array('https://twitter.com/intent/user?key=parameter&screen_name=NASA&foo=bar', array('screen_name' => 'NASA')),
            array('https://twitter.com/intent/user?user_id=73308337', array('user_id' => '73308337')),
            array('https://twitter.com/intent/user?key=parameter&user_id=73308337&foo=bar', array('user_id' => '73308337')),
        );
    }

    public function getTextForUrls()
    {
        return array(
            array('texto https://twitter.com/BigDataBlogs ácentós', array('https://twitter.com/BigDataBlogs')),
            array('blablapic.twitter.com/TdlkpWjYie', array('pic.twitter.com/TdlkpWjYie')),
            array('texto https://twitter.com/BigDataBlogs más texto blablapic.twitter.com/TdlkpWjYie foobar', array('https://twitter.com/BigDataBlogs', 'pic.twitter.com/TdlkpWjYie')),
        );
    }


}