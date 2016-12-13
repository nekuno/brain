<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\TwitterProcessor\TwitterPicProcessor;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;

class TwitterPicProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TwitterResourceOwner|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOwner;

    /**
     * @var TwitterUrlParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var TwitterPicProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\TwitterResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser')
            ->getMock();

        $this->processor = new TwitterPicProcessor($this->resourceOwner, $this->parser);
    }

    /**
     * @dataProvider getStatusForRequestItem
     */
    public function testRequestItem($url)
    {
        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $response = $this->processor->requestItem($link);

        $this->assertEquals(array(), $response, 'Asserting response for ' . $url);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $expectedArray)
    {
        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->hydrateLink($link, array());

        $this->assertEquals($expectedArray, $link->getLink()->toArray(), 'Asserting correct hydrated link for ' . $url);
    }

    /**
     * @dataProvider getResponseTags
     */
    public function testAddTags($url, $response, $expectedTags)
    {
        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->addTags($link, $response);

        $tags = $expectedTags;
        sort($tags);
        $resultTags = $link->getLink()->getTags();
        sort($resultTags);
        $this->assertEquals($tags, $resultTags);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getStatusForRequestItem()
    {
        return array(
            array(
                $this->getStatusUrl(),
            )
        );
    }

    public function getStatusForRequestItemWithEmbedded()
    {
        return array(
            array(
                $this->getStatusUrl(),
            )
        );
    }

    public function getResponseHydration()
    {
        return array(
            array(
                $this->getStatusUrl(),
                array(
                    'title' => null,
                    'description' => null,
                    'thumbnail' => null,
                    'url' => null,
                    'id' => null,
                    'tags' => array(),
                    'created' => null,
                    'processed' => true,
                    'language' => null,
                    'synonymous' => array(),
                )
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusResponse(),
                $this->getStatusTags(),
            )
        );
    }

    public function getStatusUrl()
    {
        return 'https://twitter.com/yawmoght/status/782909345961050112';
    }

    public function getStatusResponse()
    {
        return array();
    }

    public function getStatusTags()
    {
        return array();
    }

}