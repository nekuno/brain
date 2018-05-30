<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\AbstractFacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\FacebookProcessor\FacebookPageProcessor;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use Model\Link\Creator;
use Model\Token\TokensManager;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class FacebookPageProcessorTest extends AbstractProcessorTest
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
     * @var FacebookPageProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\FacebookResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser')
            ->getMock();

        $this->processor = new FacebookPageProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . FacebookUrlParser::DEFAULT_IMAGE_PATH);
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
            ->method('requestPage')
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
                $this->getPageUrl(),
                $this->getPageId(),
                false,
                $this->getProfileResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expectedLink = new Creator();
        $expectedLink->setTitle($this->getTitle());
        $expectedLink->setDescription($this->getDescription());
        $expectedLink->addAdditionalLabels(AbstractFacebookProcessor::FACEBOOK_LABEL);

        return array(
            array(
                $this->getPageUrl(),
                $this->getProfileItemResponse(),
                $expectedLink->toArray()
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getPageUrl(),
                $this->getProfileItemResponse(),
                $this->getPageTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getPageUrl(),
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
            "description" => $this->getDescription(),
            "picture" => array(
                "data" => array(
                    "is_silhouette" => false,
                    "url" => $this->getThumbnailUrl(),
                )
            ),
            "id" => "166849216704500"
        );
    }

    public function getDescription()
    {
        return "En VIPS tenemos comida para todos los gustos.

                                Más de 60 platos que puedes disfrutar durante tus desayunos, comidas, meriendas y cenas. Variados entrantes como nuestras Patatas VIPS ideales para compartir, sabrosas ensaladas como la Louisiana o la César, sandwiches como nuestro popular VIPS Club, sabrosas hamburguesas como la Manhattan, tiernas carnes como el Lomo Alto de novillo argentino y deliciosos postres y batidos. Además, para las comidas de diario podrás disfrutar de un completo Menú del día.

                                Todo ello con una insuperable relación calidad - precio y servicio WI-FI

                                ¡Te esperamos!";
    }

    public function getTitle()
    {
        return "VIPS";
    }

    public function getPageUrl()
    {
        return 'https://www.facebook.com/vips';
    }

    public function getPageId()
    {
        return array('vips');
    }

    public function getPageTags()
    {
        return array();
    }

    public function getThumbnailUrl()
    {
        return "https://scontent.xx.fbcdn.net/v/t1.0-1/p200x200/14462778_1189395474449864_8356688914233163542_n.png?oh=7896407a8bda6664154139d74b76892c&oe=5862D54B";
    }

    public function getProcessingImages()
    {
        return array (new ProcessingImage($this->getThumbnailUrl()));
    }

}