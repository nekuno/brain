<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\AbstractFacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\FacebookVideoProcessor;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use Model\Link\Video;
use Model\Token\TokensManager;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class FacebookVideoProcessorTest extends AbstractProcessorTest
{
    /**
     * @var FacebookResourceOwner|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOwner;

    /**
     * @var FacebookUrlParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var FacebookVideoProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\FacebookResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser')
            ->getMock();

        $this->processor = new FacebookVideoProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . FacebookUrlParser::DEFAULT_IMAGE_PATH);
    }

    /**
     * @dataProvider getProfileForRequestItem
     */
    public function testRequestItem($url, $id, $video)
    {
        $this->parser->expects($this->any())
            ->method('getVideoId')
            ->will($this->returnValue($id));

        $this->resourceOwner->expects($this->once())
            ->method('requestVideo')
            ->will($this->returnValue($video));

        $link = new PreprocessedLink($url);
        $link->setSource(TokensManager::FACEBOOK);
        $response = $this->processor->getResponse($link);

        $this->assertEquals($response, $video, 'Asserting correct response for ' . $url);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $id, $response, $expectedArray)
    {
        $link = new PreprocessedLink($url);
        $link->setResourceItemId($id);
        $this->processor->hydrateLink($link, $response);

        $this->assertEquals($expectedArray, $link->getFirstLink()->toArray(), 'Asserting correct hydrated link for ' . $url);
    }

    /**
     * @dataProvider getResponseTags
     */
    public function testAddTags($url, $response, $expectedTags)
    {
        $link = new PreprocessedLink($url);
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
    public function testGetImages($url, $response, $expectedImages)
    {
        $this->resourceOwner->expects($this->once())
            ->method('requestLargePicture')
            ->will($this->returnValue($this->getThumbnailUrl()));

        $this->resourceOwner->expects($this->once())
            ->method('requestSmallPicture')
            ->will($this->returnValue($this->getThumbnailUrl()));

        $link = new PreprocessedLink($url);
        $images = $this->processor->getImages($link, $response);

        $this->assertEquals($expectedImages, $images, 'Images gotten from response');
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getProfileForRequestItem()
    {
        return array(
            array(
                $this->getVideoUrl(),
                $this->getVideoId(),
                $this->getVideoResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expectedLink = new Video();
        $expectedLink->setTitle('¡Tu combo de likes más...');
        $expectedLink->setDescription("¡Tu combo de likes más rápido con el nuevo WIFI! A conectar! #wifigratis");
        $expectedLink->setEmbedId('1184085874980824');
        $expectedLink->setEmbedType('facebook');
        $expectedLink->addAdditionalLabels(AbstractFacebookProcessor::FACEBOOK_LABEL);

        return array(
            array(
                $this->getVideoUrl(),
                $this->getVideoId(),
                $this->getVideoItemResponse(),
                $expectedLink->toArray(),
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getVideoUrl(),
                $this->getVideoItemResponse(),
                $this->getVideoTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getVideoUrl(),
                $this->getVideoItemResponse(),
                $this->getProcessingImages()
            )
        );
    }

    public function getVideoResponse()
    {
        return $this->getVideoItemResponse();
    }

    public function getVideoItemResponse()
    {
        return array(
            "description" => "¡Tu combo de likes más rápido con el nuevo WIFI! A conectar! #wifigratis",
            "picture" => $this->getThumbnailUrl(),
            "permalink_url" => "/vips/videos/1184085874980824/",
            "id" => "1184085874980824"
        );
    }

    public function getVideoUrl()
    {
        return 'https://www.facebook.com/vips/videos/1184085874980824/';
    }

    public function getVideoId()
    {
        return '1184085874980824';
    }

    public function getVideoTags()
    {
        return array();
    }

    public function getThumbnailUrl()
    {
        return "https://scontent.xx.fbcdn.net/v/t15.0-10/p160x160/14510760_1184087194980692_2357859444034895872_n.jpg?oh=33727306f052fcee096c281c15c429bf&oe=586D5F95";
    }

    public function getProcessingImages()
    {
        return array (new ProcessingImage($this->getThumbnailUrl()));
    }

}