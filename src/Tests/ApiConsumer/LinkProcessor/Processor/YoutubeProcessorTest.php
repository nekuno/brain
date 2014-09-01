<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor;

use Http\OAuth\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeProcessorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var GoogleResourceOwner|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOwner;

    /**
     * @var YoutubeUrlParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    public function setUp()
    {

        $this->resourceOwner = $this->getMockBuilder('Http\OAuth\ResourceOwner\GoogleResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceOwner
            ->expects($this->any())
            ->method('authorizedAPIRequest')
            ->will($this->returnCallback(function ($url, $query) {
                return $this->getResponse($query['id']);
            }));

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser')
            ->getMock();

        $this->parser
            ->expects($this->any())
            ->method('getUrlType')
            ->will($this->returnCallback(function ($url) {
                return $this->getUrlType($url);
            }));

        $this->parser->expects($this->any())
            ->method('getYoutubeIdFromUrl')
            ->will($this->returnCallback(function () {
                return $this->getVideoId();
            }));

        $this->parser->expects($this->any())
            ->method('getChannelIdFromUrl')
            ->will($this->returnCallback(function () {
                return $this->getChannelId();
            }));

        $this->parser->expects($this->any())
            ->method('getPlaylistIdFromUrl')
            ->will($this->returnCallback(function () {
                return $this->getPlaylistId();
            }));

    }

    public function testProcessVideoUrl()
    {

        $processer = new YoutubeProcessor($this->resourceOwner, $this->parser);
        $processed = $processer->process(array(
            'url' => $this->getVideoUrl(),
        ));
        $this->assertEquals($this->getVideoUrl(), $processed['url']);
        $this->assertEquals('Tu peor error', $processed['title']);
        $this->assertEquals('En Mawi', $processed['description']);
        $tags = array(
            0 =>
                array(
                    'name' => '/m/0xgt51b',
                    'additionalLabels' =>
                        array(
                            0 => 'Freebase',
                        ),
                ),
        );
        $this->assertEquals($tags, $processed['tags']);
    }

    public function testProcessChannelUrl()
    {

        $processer = new YoutubeProcessor($this->resourceOwner, $this->parser);
        $processed = $processer->process(array(
            'url' => $this->getChannelUrl(),
        ));
        $this->assertEquals($this->getChannelUrl(), $processed['url']);
        $this->assertEquals('Efecto Pasillo', $processed['title']);
        $this->assertEquals('Canal Oficial de Youtube de Efecto Pasillo.', $processed['description']);

        $tags = array(
            0 => array('name' => '"efecto pasillo"'),
            1 => array('name' => '"pan y mantequilla"'),
            2 => array('name' => '"no importa que llueva"'),
        );
        $sortedTags = sort($tags);
        $sortedProcessedTags = sort($processed['tags']);
        $this->assertEquals($sortedTags, $sortedProcessedTags);
    }

    public function testProcesPlaylistUrl()
    {

        $processer = new YoutubeProcessor($this->resourceOwner, $this->parser);
        $processed = $processer->process(array(
            'url' => $this->getPlaylistUrl(),
        ));
        $this->assertEquals($this->getPlaylistUrl(), $processed['url']);
        $this->assertEquals('PelleK plays bad NES-games', $processed['title']);
        $this->assertEquals('', $processed['description']);
        $this->assertEmpty($processed['tags']);
    }

    /**
     * @param $response
     * @dataProvider getEmptyResponses
     */
    public function testProcessEmptyResponse($response)
    {

        $resourceOwner = $this->getMockBuilder('Http\OAuth\ResourceOwner\GoogleResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $resourceOwner
            ->expects($this->any())
            ->method('authorizedAPIRequest')
            ->will($this->returnCallback(function () use ($response) {
                return $response;
            }));

        $processer = new YoutubeProcessor($resourceOwner, $this->parser);
        $processed = $processer->process(array(
            'url' => $this->getVideoUrl(),
        ));
        $this->assertEquals($this->getVideoUrl(), $processed['url']);
        $this->assertEquals(array(), $processed['tags']);
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

    public function getResponse($id)
    {

        switch ($id) {
            case $this->getVideoId():
                return $this->getVideoResponse();
                break;
            case $this->getChannelId():
                return $this->getChannelResponse();
                break;
            case $this->getPlaylistId():
                return $this->getPlaylistResponse();
                break;
        }

        return array();
    }

    public function getUrlType($url)
    {

        switch ($url) {
            case $this->getVideoUrl():
                return YoutubeUrlParser::VIDEO_URL;
                break;
            case $this->getChannelUrl():
                return YoutubeUrlParser::CHANNEL_URL;
                break;
            case $this->getPlaylistUrl():
                return YoutubeUrlParser::PLAYLIST_URL;
                break;
        }

        return false;
    }

    public function getVideoId()
    {
        return 'zLgY05beCnY';
    }

    public function getVideoUrl()
    {
        return 'https://www.youtube.com/watch?v=zLgY05beCnY';
    }

    public function getVideoResponse()
    {
        return array(
            'kind' => 'youtube#videoListResponse',
            'etag' => '"gMjDJfS6nsym0T-NKCXALC_u_rM/Yifv0474a__DamxRo9SjojBxhAk"',
            'pageInfo' =>
                array(
                    'totalResults' => 1,
                    'resultsPerPage' => 1,
                ),
            'items' =>
                array(
                    0 =>
                        array(
                            'kind' => 'youtube#video',
                            'etag' => '"gMjDJfS6nsym0T-NKCXALC_u_rM/58qh92rlFH2F5H_uIGQnJ4pDfFM"',
                            'id' => 'zLgY05beCnY',
                            'snippet' =>
                                array(
                                    'publishedAt' => '2014-03-16T17:20:58.000Z',
                                    'channelId' => 'UCSi3NhHZWE7xXAs2NDDAxDg',
                                    'title' => 'Tu peor error',
                                    'description' => 'En Mawi',
                                    'thumbnails' =>
                                        array(
                                            'default' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/zLgY05beCnY/default.jpg',
                                                    'width' => 120,
                                                    'height' => 90,
                                                ),
                                            'medium' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/zLgY05beCnY/mqdefault.jpg',
                                                    'width' => 320,
                                                    'height' => 180,
                                                ),
                                            'high' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/zLgY05beCnY/hqdefault.jpg',
                                                    'width' => 480,
                                                    'height' => 360,
                                                ),
                                            'standard' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/zLgY05beCnY/sddefault.jpg',
                                                    'width' => 640,
                                                    'height' => 480,
                                                ),
                                            'maxres' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/zLgY05beCnY/maxresdefault.jpg',
                                                    'width' => 1280,
                                                    'height' => 720,
                                                ),
                                        ),
                                    'channelTitle' => 'Juan Luis Martinez',
                                    'categoryId' => '10',
                                    'liveBroadcastContent' => 'none',
                                ),
                            'statistics' =>
                                array(
                                    'viewCount' => '117',
                                    'likeCount' => '1',
                                    'dislikeCount' => '1',
                                    'favoriteCount' => '0',
                                    'commentCount' => '1',
                                ),
                            'topicDetails' =>
                                array(
                                    'topicIds' =>
                                        array(
                                            0 => '/m/0xgt51b',
                                        ),
                                    'relevantTopicIds' =>
                                        array(
                                            0 => '/m/0h20xml',
                                            1 => '/m/04rlf',
                                        ),
                                ),
                        ),
                ),
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
                    0 =>
                        array(
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
                                ),
                        ),
                ),
        );
    }

    public function getPlaylistId()
    {
        return 'PLcB-8ayo3tzddinO3ob7cEHhUtyyo66mN';
    }

    public function getPlaylistUrl()
    {
        return 'https://www.youtube.com/playlist?list=PLcB-8ayo3tzddinO3ob7cEHhUtyyo66mN';
    }

    public function getPlaylistResponse()
    {
        return array(
            'kind' => 'youtube#playlistListResponse',
            'etag' => '"gMjDJfS6nsym0T-NKCXALC_u_rM/0vbqmRo-1Ho63q-uB86nYn04-bU"',
            'pageInfo' =>
                array(
                    'totalResults' => 1,
                    'resultsPerPage' => 1,
                ),
            'items' =>
                array(
                    0 =>
                        array(
                            'kind' => 'youtube#playlist',
                            'etag' => '"gMjDJfS6nsym0T-NKCXALC_u_rM/7VPtzm7_MohJQf_2JJCYB47wLy4"',
                            'id' => 'PLcB-8ayo3tzddinO3ob7cEHhUtyyo66mN',
                            'snippet' =>
                                array(
                                    'publishedAt' => '2014-05-26T13:57:32.000Z',
                                    'channelId' => 'UCNvTrGFQXu2h5dxpJdZlySw',
                                    'title' => 'PelleK plays bad NES-games',
                                    'description' => '',
                                    'thumbnails' =>
                                        array(
                                            'default' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/02dFn6UK1ak/default.jpg',
                                                    'width' => 120,
                                                    'height' => 90,
                                                ),
                                            'medium' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/02dFn6UK1ak/mqdefault.jpg',
                                                    'width' => 320,
                                                    'height' => 180,
                                                ),
                                            'high' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/02dFn6UK1ak/hqdefault.jpg',
                                                    'width' => 480,
                                                    'height' => 360,
                                                ),
                                            'standard' =>
                                                array(
                                                    'url' => 'https://i.ytimg.com/vi/02dFn6UK1ak/sddefault.jpg',
                                                    'width' => 640,
                                                    'height' => 480,
                                                ),
                                        ),
                                    'channelTitle' => 'pellekofficial2',
                                ),
                            'status' =>
                                array(
                                    'privacyStatus' => 'public',
                                ),
                        ),
                ),
        );
    }

}