<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\YoutubePlaylistProcessor;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;

class YoutubePlaylistProcessorTest extends \PHPUnit_Framework_TestCase
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
     * @var YoutubePlaylistProcessor
     */
    protected $processor;

    public function setUp()
    {

        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\GoogleResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();


        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser')
            ->getMock();

        $this->processor = new YoutubePlaylistProcessor($this->resourceOwner, $this->parser);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->setExpectedException('ApiConsumer\Exception\CannotProcessException', 'Could not process url ' . $url);

        $this->parser->expects($this->once())
            ->method('getPlaylistId')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $this->processor->requestItem($link);
    }

    /**
     * @dataProvider getPlaylistForRequestItem
     */
    public function testRequestItem($url, $id, $playlist)
    {
        $this->parser->expects($this->once())
            ->method('getPlaylistId')
            ->will($this->returnValue(array('id' => $id)));

        $this->resourceOwner->expects($this->once())
            ->method('requestPlaylist')
            ->will($this->returnValue($playlist));

        $link = new PreprocessedLink($url);
        $response = $this->processor->requestItem($link);

        $this->assertEquals($this->getPlaylistItemResponse(), $response, 'Asserting correct playlistresponse for ' . $url);
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

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getPlaylistForRequestItem()
    {
        return array(
            array(
                $this->getPlaylistUrl(),
                $this->getPlaylistId(),
                $this->getPlaylistResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        return array(
            array(
                $this->getPlaylistUrl(),
                $this->getPlaylistId(),
                $this->getPlaylistItemResponse(),
                array(
                    'title' => 'PelleK plays bad NES-games',
                    'description' => '',
                    'thumbnail' => null,
                    'url' => null,
                    'id' => null,
                    'tags' => array(),
                    'created' => null,
                    'processed' => true,
                    'language' => null,
                    'synonymous' => array(),
                    'imageProcessed' => null,
                    'embed_type' => 'youtube',
                    'embed_id' => 'PLcB-8ayo3tzddinO3ob7cEHhUtyyo66mN',
                )
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getPlaylistUrl(),
                $this->getPlaylistItemResponse(),
                $this->getPlaylistTags(),
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

    public function getVideoId()
    {
        return 'zLgY05beCnY';
    }

    public function getVideoUrl()
    {
        return 'https://www.youtube.com/watch?v=zLgY05beCnY';
    }

    public function getVideoTags()
    {
        return array(
            0 =>
                array(
                    'name' => '/m/0xgt51b',
                    'additionalLabels' =>
                        array(
                            0 => 'Freebase',
                        ),
                ),
        );
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
                    0 => $this->getVideoItemResponse()
                ),
        );
    }

    public function getVideoItemResponse()
    {
        return array(
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
                    0 => $this->getPlaylistItemResponse(),
                ),
        );
    }

    public function getPlaylistItemResponse()
    {
        return array(
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
                    0 => $this->getPlaylistItemResponse(),
                ),
        );
    }

    public function getPlaylistTags()
    {
        return array();
    }

}