<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\TumblrProcessor\AbstractTumblrProcessor;
use ApiConsumer\LinkProcessor\Processor\TumblrProcessor\TumblrBlogProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use ApiConsumer\ResourceOwner\TumblrResourceOwner;
use Model\Link\Creator;
use Model\Link\Link;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class TumblrBlogProcessorTest extends AbstractProcessorTest
{
    /**
     * @var TumblrResourceOwner|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOwner;

    /**
     * @var TumblrUrlParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var TumblrBlogProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\TumblrResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser')
            ->getMock();

        $this->processor = new TumblrBlogProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->expectException(UrlNotValidException::class);

        $this->parser->expects($this->any())
            ->method('isBlogUrl')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getBlogForRequestItem
     */
    public function testRequestItem($url, $blog)
    {
        $this->resourceOwner->expects($this->once())
            ->method('requestBlog')
            ->will($this->returnValue($blog));

        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $response, $expectedArray)
    {
        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);
        $this->processor->hydrateLink($link, $response);

        $this->assertEquals($expectedArray, $link->getFirstLink()->toArray(), 'Asserting correct hydrated link for ' . $url);
    }

    /**
     * @dataProvider getResponseTags
     */
    public function testAddTags($url, $response, $expectedTags)
    {
        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);
        $this->processor->addTags($link, $response);

        $tags = $expectedTags;
        sort($tags);
        $resultTags = $link->getFirstLink()->getTags();
        sort($resultTags);
        $this->assertEquals($tags, $resultTags);
    }

    /**
     * @dataProvider getResponseImages
     */
    public function testGetImages($url, $response)
    {
        $this->resourceOwner->expects($this->exactly(3))
            ->method('requestBlogAvatar')
            ->will($this->returnValueMap($this->getProcessingImages()));

        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);

        $this->processor->getImages($link, $response);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getBlogForRequestItem()
    {
        return array(
            array(
                $this->getBlogUrl(),
                $this->getBlogResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expected = new Link();
        $expected->setUrl($this->getBlogUrl());
        $expected->addAdditionalLabels(AbstractTumblrProcessor::TUMBLR_LABEL);
        $expected->addAdditionalLabels(Creator::CREATOR_LABEL);

        return array(
            array(
                $this->getBlogUrl(),
                $this->getBlogResponse(),
                $expected->toArray(),
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getBlogUrl(),
                $this->getBlogResponse(),
                $this->getBlogTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getBlogUrl(),
                $this->getBlogResponse(),
            )
        );
    }

    public function getBlogResponse()
    {
        return array(
            'response' => array(
                'blog' => array(
                    'admin' => true,
                    'ask' => false,
                    'ask_anon' => false,
                    'ask_page_title' => "Ask me anything",
                    'can_send_fan_mail' => true,
                    'can_subscribe' => false,
                    'description' => "",
                    'drafts' => 0,
                    'facebook' => "N",
                    'facebook_opengraph_enabled' => "N",
                    'followed' => false,
                    'followers' => 1,
                    'is_adult' => false,
                    'is_blocked_from_primary' => false,
                    'is_nsfw' => false,
                    'likes' => 6,
                    'messages' => 0,
                    'name' => "acabrillanes",
                    'posts' => 3,
                    'primary' => true,
                    'queue' => 0,
                    'reply_conditions' => "3",
                    'share_likes' => true,
                    'subscribed' => false,
                    'title' => "Sin título",
                    'total_posts' => 3,
                    'tweet' => "N",
                    'twitter_enabled' => false,
                    'twitter_send' => false,
                    'type' => "public",
                    'updated' => 1510918904,
                    'url' => "https://acabrillanes.tumblr.com/",
                    'is_optout_ads' => true,
                )
            )
        );
    }

    public function getBlogUrl()
    {
        return 'https://acabrillanes.tumblr.com/';
    }

    public function getBlogTags()
    {
        return array();
    }

    public function getThumbnail($size)
    {
        return "https://api.tumblr.com/v2/blog/acabrillanes/avatar/$size";
    }

    public function getProcessingImages()
    {
        return array(
            new ProcessingImage($this->getThumbnail(512)),
            new ProcessingImage($this->getThumbnail(128)),
            new ProcessingImage($this->getThumbnail(96)),
        );
    }

}