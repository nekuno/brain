<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\AbstractYoutubeProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\YoutubePlaylistProcessor;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Link\Video;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class YoutubePlaylistProcessorTest extends AbstractProcessorTest
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

        $this->processor = new YoutubePlaylistProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl . YoutubeUrlParser::DEFAULT_IMAGE_PATH);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->expectException(CannotProcessException::class);

        $this->parser->expects($this->once())
            ->method('getPlaylistId')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $this->processor->getResponse($link);
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
        $response = $this->processor->getResponse($link);

        $this->assertEquals($this->getPlaylistResponse(), $response, 'Asserting correct playlistresponse for ' . $url);
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
        $expected = new Video();
        $expected->setTitle('PelleK plays bad NES-games');
        $expected->setDescription('');
        $expected->setEmbedType('youtube');
        $expected->setEmbedId('PLcB-8ayo3tzddinO3ob7cEHhUtyyo66mN');
        $expected->addAdditionalLabels(AbstractYoutubeProcessor::YOUTUBE_LABEL);

        return array(
            array(
                $this->getPlaylistUrl(),
                $this->getPlaylistId(),
                $this->getPlaylistResponse(),
                $expected->toArray(),
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

    public function getResponseImages()
    {
        return array(
            array(
                $this->getPlaylistUrl(),
                $this->getPlaylistItemResponse(),
                $this->getProcessingImages()
            )
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

    public function getProcessingImages()
    {
        return array ();
    }
}