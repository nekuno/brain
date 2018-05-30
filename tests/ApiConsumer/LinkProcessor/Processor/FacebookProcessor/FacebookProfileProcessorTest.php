<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\AbstractFacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\FacebookProfileProcessor;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use Model\Link\Link;
use Model\Token\TokensManager;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class FacebookProfileProcessorTest extends AbstractProcessorTest
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
     * @var FacebookProfileProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\FacebookResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser')
            ->getMock();

        $this->processor = new FacebookProfileProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . FacebookUrlParser::DEFAULT_IMAGE_PATH);
    }

    /**
     * @dataProvider getProfileForRequestItem
     */
    public function testRequestItem($url, $id, $isStatus, $profile)
    {
        $this->parser->expects($this->any())
            ->method('isStatusId')
            ->will($this->returnValue($isStatus));

        $this->resourceOwner->expects($this->once())
            ->method('requestProfile')
            ->will($this->returnValue($profile));

        $link = new PreprocessedLink($url);
        $link->setResourceItemId($id);
        $link->setType(FacebookUrlParser::FACEBOOK_PAGE);
        $link->setSource(TokensManager::FACEBOOK);
        $response = $this->processor->getResponse($link);

        $this->assertEquals($response, $profile, 'Asserting correct response for ' . $url);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $response, $expectedArray)
    {
        $link = new PreprocessedLink($url);
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
                $this->getProfileUrl(),
                $this->getProfileId(),
                false,
                $this->getProfileResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expectedLink = new Link();
        $expectedLink->setTitle($this->getTitle());
        $expectedLink->setDescription($this->getTitle());
        $expectedLink->addAdditionalLabels(AbstractFacebookProcessor::FACEBOOK_LABEL);
        return array(
            array(
                $this->getProfileUrl(),
                $this->getProfileItemResponse(),
                $expectedLink->toArray()
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getProfileUrl(),
                $this->getProfileItemResponse(),
                $this->getProfileTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getProfileUrl(),
                $this->getProfileItemResponse(),
                $this->getProcessingImages()
            )
        );
    }

    public function getProfileResponse()
    {
        return $this->getProfileItemResponse();
    }

    public function getProfileItemResponse()
    {
        return array(
            "name" => $this->getTitle(),
            "picture" => array(
                "data" => array(
                    "is_silhouette" => false,
                    "url" => $this->getThumbnailUrl(),
                )
            ),
            "id" => "10153571968389307"
        );
    }

    public function getProfileUrl()
    {
        return 'https://www.facebook.com/vips';
    }

    public function getProfileId()
    {
        return array('vips');
    }

    public function getProfileTags()
    {
        return array();
    }

    public function getThumbnailUrl()
    {
        return "https://scontent.xx.fbcdn.net/v/t1.0-1/p200x200/1936476_10154428007884307_1240327335205953613_n.jpg?oh=2f4b121a7b7baf85328495f15ebd368e&oe=594A6F24";
    }

    public function getProcessingImages()
    {
        return array (new ProcessingImage($this->getThumbnailUrl()));
    }

    public function getTitle()
    {
        return "Roberto Martinez Pallarola";
    }
}