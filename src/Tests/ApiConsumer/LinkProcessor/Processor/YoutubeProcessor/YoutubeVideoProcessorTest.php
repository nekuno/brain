<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\YoutubeVideoProcessor;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;

class YoutubeVideoProcessorTest extends \PHPUnit_Framework_TestCase
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
     * @var YoutubeVideoProcessor
     */
    protected $processor;

    public function setUp()
    {

        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\GoogleResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser')
            ->getMock();

        $this->processor = new YoutubeVideoProcessor($this->resourceOwner, $this->parser);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->setExpectedException('ApiConsumer\Exception\CannotProcessException', 'Could not process url ' . $url);

        $this->parser->expects($this->once())
            ->method('getVideoId')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getVideoForRequestItem
     */
    public function testRequestItem($url, $id, $video)
    {
        $this->parser->expects($this->once())
            ->method('getVideoId')
            ->will($this->returnValue(array('id' => $id)));

        $this->resourceOwner->expects($this->once())
            ->method('requestVideo')
            ->will($this->returnValue($video));

        $link = new PreprocessedLink($url);
        $response = $this->processor->getResponse($link);

        $this->assertEquals($this->getVideoItemResponse(), $response, 'Asserting correct video response for ' . $url);
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

    public function getVideoForRequestItem()
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
        return array(
            array(
                $this->getVideoUrl(),
                $this->getVideoId(),
                $this->getVideoItemResponse(),
                array(
                    'title' => 'Tu peor error',
                    'description' => 'En Mawi',
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
                    'embed_id' => 'zLgY05beCnY',
                )
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
}