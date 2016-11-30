<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;

class FacebookUrlParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var FacebookUrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new FacebookUrlParser();
    }

    /**
     * @param $url
     * @dataProvider getBadUrls
     */
    public function testBadUrls($url){
        $this->setExpectedException(UrlNotValidException::class, 'Url '.$url.' not valid');
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
        $this->assertEquals($expectedType, $type, 'Asserting that ' . $url . ' is a ' .$expectedType);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlVideoIds($url)
    {
        $this->setExpectedException(UrlNotValidException::class, 'Url '.$url.' not valid');
        $this->parser->getUrlType($url);
    }

    /**
     * @dataProvider getUrlsForVideoId
     */
    public function testVideosIds($url, $expectedId)
    {
        $id = $this->parser->getVideoId($url);
        $this->assertEquals($expectedId, $id, 'Asserting that ' . $url . ' has video id ' . json_encode($expectedId));
    }

    /**
     * @dataProvider getIdsForStatus
     */
    public function isStatusIds($id, $expectedIsStatus){
        $isStatus = $this->parser->isStatusId($id);
        $this->assertEquals($expectedIsStatus, $isStatus, 'Asserting that status "'.$id.'" is status == '. $expectedIsStatus);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url'),
            array('http://www.google.es'),
            array('http://www.twitter.com/'),
            array('http://twitter.com/tweet/not')
        );
    }

    public function getUrlsForType()
    {
        return array(
            array('http://www.facebook.com/vips', FacebookUrlParser::FACEBOOK_PROFILE),
            array('https://www.facebook.com/roberto.m.pallarola', FacebookUrlParser::FACEBOOK_PROFILE),
        );
    }

    public function getUrlsForVideoId()
    {
        return array(
            array('https://www.facebook.com/vips/videos/1177028542353224/', '1177028542353224'),
        );
    }

    public function getIdsForStatus()
    {
        return array(
            array('10153571968389307_10155257143354307', true),
            array('10153571968389307', false),
            array(10153571968389307, false),
        );
    }


}