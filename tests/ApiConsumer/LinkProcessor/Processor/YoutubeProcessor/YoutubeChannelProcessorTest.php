<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\AbstractYoutubeProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\YoutubeChannelProcessor;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Link\Link;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class YoutubeChannelProcessorTest extends AbstractProcessorTest
{

    /**
     * @var GoogleResourceOwner|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOwner;

    /**
     * @var YoutubeUrlParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var YoutubeChannelProcessor
     */
    protected $processor;

    public function setUp()
    {

        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\GoogleResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser')
            ->getMock();

        $this->processor = new YoutubeChannelProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . YoutubeUrlParser::DEFAULT_IMAGE_PATH);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->expectException(CannotProcessException::class);

        $this->parser->expects($this->once())
            ->method('getChannelId')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getChannelForRequestItem
     */
    public function testRequestItem($url, $id, $channel)
    {
        $this->parser->expects($this->once())
            ->method('getChannelId')
            ->will($this->returnValue(array('id' => $id)));

        $this->resourceOwner->expects($this->once())
            ->method('requestChannel')
            ->will($this->returnValue($channel));

        $link = new PreprocessedLink($url);
        $response = $this->processor->getResponse($link);

        $this->assertEquals($this->getChannelResponse(), $response, 'Asserting correct video response for ' . $url);
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

    public function getChannelForRequestItem()
    {
        return array(
            array(
                $this->getChannelUrl(),
                $this->getChannelId(),
                $this->getChannelResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expected = new Link();
        $expected->setTitle('Efecto Pasillo');
        $expected->setDescription('Canal Oficial de Youtube de Efecto Pasillo.');
        $expected->addAdditionalLabels(AbstractYoutubeProcessor::YOUTUBE_LABEL);

        return array(
            array(
                $this->getChannelUrl(),
                $this->getChannelId(),
                $this->getChannelResponse(),
                $expected->toArray(),
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getChannelUrl(),
                $this->getChannelItemResponse(),
                $this->getChannelTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getChannelUrl(),
                $this->getChannelItemResponse(),
                $this->getProcessingImages()
            )
        );
    }

    public function getEmptyResponses()
    {
        return array(
            array(array()),
            array(array('items' => '')),
            array(array('items' => null)),
            array(array('items' => array())),
        );
    }

    public function getChannelId()
    {
        return 'UCLbjQpHFa_x40v-uY88y4Qw';
    }

    public function getChannelUrl()
    {
        return 'https://www.youtube.com/channel/UCLbjQpHFa_x40v-uY88y4Qw';
    }

    public function getChannelResponse()
    {
        return array(
            'kind' => 'youtube#channelListResponse',
            'etag' => '"gMjDJfS6nsym0T-NKCXALC_u_rM/itW5VqdpqChVljMs6wQSMqxhyEY"',
            'pageInfo' =>
                array(
                    'totalResults' => 1,
                    'resultsPerPage' => 1,
                ),
            'items' =>
                array(
                    0 => $this->getChannelItemResponse(),
                ),
        );
    }

    public function getChannelItemResponse()
    {
        return array(
            'kind' => 'youtube#channel',
            'etag' => '"gMjDJfS6nsym0T-NKCXALC_u_rM/gKYxxP_iOrJxfOfh0DhRnE8svLg"',
            'id' => 'UCLbjQpHFa_x40v-uY88y4Qw',
            'snippet' =>
                array(
                    'title' => 'Efecto Pasillo',
                    'description' => 'Canal Oficial de Youtube de Efecto Pasillo.',
                    'publishedAt' => '2011-11-24T12:39:11.000Z',
                    'thumbnails' =>
                        array(
                            'default' =>
                                array(
                                    'url' => 'https://yt3.ggpht.com/-a3qMwcBYLnY/AAAAAAAAAAI/AAAAAAAAAAA/9b8qMAiJUjU/s88-c-k-no/photo.jpg',
                                ),
                            'medium' =>
                                array(
                                    'url' => 'https://yt3.ggpht.com/-a3qMwcBYLnY/AAAAAAAAAAI/AAAAAAAAAAA/9b8qMAiJUjU/s240-c-k-no/photo.jpg',
                                ),
                            'high' =>
                                array(
                                    'url' => 'https://yt3.ggpht.com/-a3qMwcBYLnY/AAAAAAAAAAI/AAAAAAAAAAA/9b8qMAiJUjU/s240-c-k-no/photo.jpg',
                                ),
                        ),
                ),
            'contentDetails' =>
                array(
                    'relatedPlaylists' =>
                        array(
                            'likes' => 'LLLbjQpHFa_x40v-uY88y4Qw',
                            'favorites' => 'FLLbjQpHFa_x40v-uY88y4Qw',
                            'uploads' => 'UULbjQpHFa_x40v-uY88y4Qw',
                        ),
                    'googlePlusUserId' => '111964937351359566396',
                ),
            'statistics' =>
                array(
                    'viewCount' => '36202068',
                    'commentCount' => '57',
                    'subscriberCount' => '68402',
                    'hiddenSubscriberCount' => false,
                    'videoCount' => '63',
                ),
            'topicDetails' =>
                array(
                    'topicIds' =>
                        array(
                            0 => '/m/0qd4wdx',
                            1 => '/m/04rlf',
                        ),
                ),
            'brandingSettings' =>
                array(
                    'channel' =>
                        array(
                            'title' => 'Efecto Pasillo',
                            'description' => 'Canal Oficial de Youtube de Efecto Pasillo.',
                            'keywords' => '"efecto pasillo" "pan y mantequilla" "no importa que llueva"',
                            'showRelatedChannels' => true,
                            'showBrowseView' => true,
                            'featuredChannelsTitle' => 'Canales destacados',
                            'featuredChannelsUrls' =>
                                array(
                                    0 => 'UCvFbxQEjxr-hsNRFMQpE7PA',
                                    1 => 'UCdlD_oF3QPiwMa3eJFg-9qg',
                                ),
                            'unsubscribedTrailer' => '7WxmMGh73mw',
                            'profileColor' => '#000000',
                        ),
                    'image' =>
                        array(
                            'bannerImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w1060-fcrop64=1,00005a57ffffa5a8-nd/channels4_banner.jpg',
                            'bannerMobileImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w640-fcrop64=1,32b75a57cd48a5a8-nd/channels4_banner.jpg',
                            'bannerTabletLowImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w1138-fcrop64=1,00005a57ffffa5a8-nd/channels4_banner.jpg',
                            'bannerTabletImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w1707-fcrop64=1,00005a57ffffa5a8-nd/channels4_banner.jpg',
                            'bannerTabletHdImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w2276-fcrop64=1,00005a57ffffa5a8-nd/channels4_banner.jpg',
                            'bannerTabletExtraHdImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w2560-fcrop64=1,00005a57ffffa5a8-nd/channels4_banner.jpg',
                            'bannerMobileLowImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w320-fcrop64=1,32b75a57cd48a5a8-nd/channels4_banner.jpg',
                            'bannerMobileMediumHdImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w960-fcrop64=1,32b75a57cd48a5a8-nd/channels4_banner.jpg',
                            'bannerMobileHdImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w1280-fcrop64=1,32b75a57cd48a5a8-nd/channels4_banner.jpg',
                            'bannerMobileExtraHdImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w1440-fcrop64=1,32b75a57cd48a5a8-nd/channels4_banner.jpg',
                            'bannerTvImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w2120-fcrop64=1,00000000ffffffff-nd/channels4_banner.jpg',
                            'bannerTvLowImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w854-fcrop64=1,00000000ffffffff-nd/channels4_banner.jpg',
                            'bannerTvMediumImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w1280-fcrop64=1,00000000ffffffff-nd/channels4_banner.jpg',
                            'bannerTvHighImageUrl' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w1920-fcrop64=1,00000000ffffffff-nd/channels4_banner.jpg',
                        ),
                    'hints' =>
                        array(
                            0 =>
                                array(
                                    'property' => 'channel.modules.show_comments.bool',
                                    'value' => 'True',
                                ),
                            1 =>
                                array(
                                    'property' => 'channel.banner.image_height.int',
                                    'value' => '0',
                                ),
                            2 =>
                                array(
                                    'property' => 'channel.featured_tab.template.string',
                                    'value' => 'Everything',
                                ),
                            3 =>
                                array(
                                    'property' => 'channel.banner.mobile.medium.image.url',
                                    'value' => 'https://yt3.ggpht.com/-XhE5qPJs1GM/Uno14GFew-I/AAAAAAAAAC4/VDAGf8mn7H4/w640-fcrop64=1,32b75a57cd48a5a8-nd/channels4_banner.jpg',
                                ),
                        ),
                )
        );
    }

    public function getChannelTags()
    {
        return array(
            0 => array('name' => '"efecto pasillo"'),
            1 => array('name' => '"pan y mantequilla"'),
            2 => array('name' => '"no importa que llueva"'),
        );
    }

    public function getProcessingImages()
    {
        $smallProcessingImage = new ProcessingImage('https://yt3.ggpht.com/-a3qMwcBYLnY/AAAAAAAAAAI/AAAAAAAAAAA/9b8qMAiJUjU/s88-c-k-no/photo.jpg');
        $smallProcessingImage->setHeight(88);
        $smallProcessingImage->setWidth(88);
        $smallProcessingImage->setLabel(ProcessingImage::LABEL_SMALL);

        $mediumProcessingImage = new ProcessingImage('https://yt3.ggpht.com/-a3qMwcBYLnY/AAAAAAAAAAI/AAAAAAAAAAA/9b8qMAiJUjU/s240-c-k-no/photo.jpg');
        $mediumProcessingImage->setHeight(240);
        $mediumProcessingImage->setWidth(240);
        $mediumProcessingImage->setLabel(ProcessingImage::LABEL_MEDIUM);

        $largeProcessingImage = new ProcessingImage('https://yt3.ggpht.com/-a3qMwcBYLnY/AAAAAAAAAAI/AAAAAAAAAAA/9b8qMAiJUjU/s240-c-k-no/photo.jpg');
        $largeProcessingImage->setHeight(240);
        $largeProcessingImage->setWidth(240);
        $largeProcessingImage->setLabel(ProcessingImage::LABEL_LARGE);

        return array($smallProcessingImage, $mediumProcessingImage, $largeProcessingImage);
    }
}