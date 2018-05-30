<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\AbstractFacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\FacebookStatusProcessor;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use Model\Link\Link;
use Model\Token\TokensManager;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class FacebookStatusProcessorTest extends AbstractProcessorTest
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
     * @var FacebookStatusProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\FacebookResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser')
            ->getMock();

        $this->processor = new FacebookStatusProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . FacebookUrlParser::DEFAULT_IMAGE_PATH);
    }

    /**
     * @dataProvider getStatusForRequestItem
     */
    public function testRequestItem($url, $id, $isStatus, $profile)
    {
        $this->parser->expects($this->any())
            ->method('isStatusId')
            ->will($this->returnValue($isStatus));

        $this->resourceOwner->expects($this->once())
            ->method('requestStatus')
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

    public function getStatusForRequestItem()
    {
        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusId(),
                true,
                $this->getStatusResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expectedLink = new Link();
        $expectedLink->setTitle($this->getTitle());
        $expectedLink->setDescription($this->getDescription());
        $expectedLink->addAdditionalLabels(AbstractFacebookProcessor::FACEBOOK_LABEL);

        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusItemResponse(),
                $expectedLink->toArray(),
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusItemResponse(),
                $this->getProfileTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusItemResponse(),
                $this->getProcessingImages()
            )
        );
    }

    public function getStatusResponse()
    {
        return $this->getStatusItemResponse();
    }

    public function getStatusItemResponse()
    {
        return array(
            "name" => $this->getTitle(),
            "description" => $this->getDescription(),
            "full_picture" => $this->getThumbnailUrl(),
            "id" => "10153571968389307_10155807625414307"
        );
    }

    public function getDescription()
    {
        return "A veces un pequeño cambio en el orden de las palabras es suficiente para darnos cuenta de la barbaridad que estamos diciendo. Ante la afirmación \"somos adi";
    }

    public function getTitle()
    {
        return "Desmontando \"Salvados: conectados\" y la adicción al móvil - blogoff";
    }

    public function getStatusUrl()
    {
        return 'https://www.facebook.com/vips';
    }

    public function getStatusId()
    {
        return array('vips');
    }

    public function getProfileTags()
    {
        return array();
    }

    public function getThumbnailUrl()
    {
        return "https://external.xx.fbcdn.net/safe_image.php?d=AQACtmgJeS0HzivW&w=130&h=130&url=http%3A%2F%2Fwww.blogoff.es%2Fwp-content%2Fuploads%2F2017%2F02%2Flacie-3.jpg&cfs=1&sx=497&sy=0&sw=994&sh=994&_nc_hash=AQBOtGIg5toSeT1_";
    }

    public function getProcessingImages()
    {
        return array (new ProcessingImage($this->getThumbnailUrl()));
    }
}