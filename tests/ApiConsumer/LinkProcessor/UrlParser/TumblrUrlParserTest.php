<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use PHPUnit\Framework\TestCase;

class TumblrUrlParserTest extends TestCase
{

    /**
     * @var TumblrUrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new TumblrUrlParser();
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
        $this->assertEquals($expectedType, $type, 'Asserting that ' . $url . ' is a ' . $expectedType);
    }

    /**
     * @param $url
     * @param $expectedId
     * @dataProvider getUrlsForBlogId
     */
    public function testBlogId($url, $expectedId)
    {
        $url = $this->parser->cleanURL($url);
        $id = $this->parser->getBlogId($url);
        $this->assertEquals($expectedId, $id, 'Asserting that ' . $url . ' has blog id ' . $id);
    }

    /**
     * @param $url
     * @param $expectedId
     * @dataProvider getUrlsForPostId
     */
    public function testPostId($url, $expectedId)
    {
        $url = $this->parser->cleanURL($url);
        $id = $this->parser->getPostId($url);
        $this->assertEquals($expectedId, $id, 'Asserting that ' . $url . ' has post id ' . $id);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url'),
            array('http://www.google.es'),
            array('https://tumblr.com/pot/not'),
            array('https://www.tumblr.com/not'),
        );
    }

    public function getUrlsForType()
    {
        return array(
            array('https://acabrillanes.tumblr.com/post/167586175102/itransformeres', TumblrUrlParser::TUMBLR_UNKNOWN_TYPE_POST),
            array('http://castoffcrown.tumblr.com/post/76328517021/kyuss-50-million-year-trip-downside-up', TumblrUrlParser::TUMBLR_UNKNOWN_TYPE_POST),
            array('https://acabrillanes.tumblr.com/', TumblrUrlParser::TUMBLR_BLOG),
            array('https://acabrillanes.tumblr.com/post/167586206692/aplicaci%C3%B3n-de-filtros-para-procesamiento-y', TumblrUrlParser::TUMBLR_UNKNOWN_TYPE_POST),
            array('http://nikk-mayson.tumblr.com/post/165234856410', TumblrUrlParser::TUMBLR_UNKNOWN_TYPE_POST),
            array('https://javascriptpro.tumblr.com/post/167585084909/redux-vs-mobx-which-is-best-for-your-project', TumblrUrlParser::TUMBLR_UNKNOWN_TYPE_POST),
        );
    }

    public function getUrlsForBlogId()
    {
        return array(
            array('https://acabrillanes.tumblr.com/', 'acabrillanes.tumblr.com'),
            array('https://tumblr.com/blog/acabrillanes/', 'acabrillanes'),
            array('https://javascriptpro.tumblr.com/post/167585084909/redux-vs-mobx-which-is-best-for-your-project', 'javascriptpro.tumblr.com'),
            array('http://nikk-mayson.tumblr.com/post/165234856410', 'nikk-mayson.tumblr.com'),
        );
    }

    public function getUrlsForPostId()
    {
        return array(
            array('https://acabrillanes.tumblr.com/post/167586175102/itransformeres', '167586175102'),
            array('https://acabrillanes.tumblr.com/post/167586206692/aplicaci%C3%B3n-de-filtros-para-procesamiento-y', '167586206692'),
            array('http://nikk-mayson.tumblr.com/post/165234856410', '165234856410'),
            array('https://javascriptpro.tumblr.com/post/167585084909/redux-vs-mobx-which-is-best-for-your-project', '167585084909'),
        );
    }
}