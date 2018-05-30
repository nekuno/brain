<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\TumblrProcessor\AbstractTumblrProcessor;
use ApiConsumer\LinkProcessor\Processor\TumblrProcessor\TumblrUnknownTypePostProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use ApiConsumer\ResourceOwner\TumblrResourceOwner;
use Model\Link\Link;
use Tests\ApiConsumer\LinkProcessor\Processor\AbstractProcessorTest;

class TumblrUnknownTypePostProcessorTest extends AbstractProcessorTest
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
     * @var TumblrUnknownTypePostProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\TumblrResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser')
            ->getMock();

        $this->processor = new TumblrUnknownTypePostProcessor($this->resourceOwner, $this->parser, $this->brainBaseUrl);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->expectException(UrlNotValidException::class);

        $this->parser->expects($this->any())
            ->method('isPostUrl')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getPostForRequestItem
     */
    public function testRequestItem($url, $post)
    {
        $this->resourceOwner->expects($this->once())
            ->method('requestPost')
            ->will($this->returnValue($post));

        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);
        $this->processor->getResponse($link);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $response)
    {
        $this->expectException(UrlChangedException::class);
        $link = new PreprocessedLink($url);
        $firstLink = Link::buildFromArray(array('url' => $url));
        $link->setFirstLink($firstLink);
        $this->processor->setType('photo');
        $this->processor->hydrateLink($link, $response);
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
        $this->processor->setType('photo');
        $this->processor->getImages($link, $response);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getPostForRequestItem()
    {
        return array(
            array(
                $this->getPostUrl(),
                $this->getPostResponse(),
            )
        );
    }

    public function getResponseHydration()
    {
        $expected = new Link();
        $expected->setUrl($this->getPostUrl());
        $expected->setTitle("Redux vs MobX: Which Is Best for Your Project? ☞");
        $expected->setDescription("..");
        $expected->addAdditionalLabels(AbstractTumblrProcessor::TUMBLR_LABEL);

        return array(
            array(
                $this->getPostUrl(),
                $this->getPostData(),
                $expected->toArray(),
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getPostUrl(),
                $this->getPostResponse(),
                $this->getPostTags(),
            )
        );
    }

    public function getResponseImages()
    {
        return array(
            array(
                $this->getPostUrl(),
                $this->getPostResponse(),
            )
        );
    }

    public function getPostResponse()
    {
        return array(
            'response' => array(
                'posts' => array($this->getPostData())
            )
        );
    }

    public function getPostData()
    {
        return array(
            'type' => "photo",
            'blog_name' => "acabrillanes",
            'id' => 167585735597,
            'post_url' => "https://acabrillanes.tumblr.com/post/167585735597/javascriptpro-redux-vs-mobx-which-is-best-for",
            'slug' => "javascriptpro-redux-vs-mobx-which-is-best-for",
            'date' => "2017-11-17 11:13:48 GMT",
            'timestamp' => 1510917228,
            'state' => "published",
            'format' => "html",
            'reblog_key' => "X0MBLoHf",
            'tags' => array(),
            'short_url' => "https://tmblr.co/ZpQi2c2S4usEj",
            'summary' => "Redux vs MobX: Which Is Best for Your Project? ☞...",
            'is_blocks_post_format' => false,
            'recommended_source' => NULL,
            'recommended_color' => NULL,
            'followed' => false,
            'liked' => false,
            'note_count' => 2,
            'caption' => "<p><a href=\"https://javascriptpro.tumblr.com/post/167585084909/redux-vs-mobx-which-is-best-for-your-project\" class=\"tumblr_blog\">javascriptpro</a>:</p><blockquote>
                    <p>Redux vs MobX: Which Is Best for Your Project?<br/>
                    ☞ <a href=\"https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project\">https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project</a></p>
                    
                    <p>#reactjs #Redux</p>
                    </blockquote>",
            'reblog' => array(
                'comment' => "",
                'tree_html' => "<p><a href=\"https://javascriptpro.tumblr.com/post/167585084909/redux-vs-mobx-which-is-best-for-your-project\" class=\"tumblr_blog\">javascriptpro</a>:</p>
                    <blockquote>
                    <p>Redux vs MobX: Which Is Best for Your Project?<br>
                    ☞ <a href=\"https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project\">https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project</a></p>
                    
                    <p>#reactjs #Redux</p>
                    </blockquote>"
            ),
            'trail' =>
                array(
                    array(
                        'blog' => array(),
                        'post' => array(),
                        'content_raw' => "<p>Redux vs MobX: Which Is Best for Your Project?<br>
                    ☞ <a href=\"https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project\">https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project</a></p>
                    
                    <p>#reactjs #Redux</p>",
                        'content' => "<p>Redux vs MobX: Which Is Best for Your Project?<br />
                    &#9758; <a href=\"https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project\">https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project</a></p>
                    
                    <p>#reactjs #Redux</p>",
                        'is_root_item' => true
                    )
                ),
            'link_url' => "https://school.geekwall.in/p/SJR96izJM/redux-vs-mobx-which-is-best-for-your-project",
            'image_permalink' => "https://acabrillanes.tumblr.com/image/167585735597",
            'photos' => array(
                array(
                    'caption' => "",
                    'original_size' => array(),
                    'alt_sizes' => array(),
                )
            ),
            'can_like' => false,
            'can_reblog' => true,
            'can_send_in_message' => true,
            'can_reply' => true,
            'display_avatar' => true,
        );
    }

    public function getPostUrl()
    {
        return 'https://acabrillanes.tumblr.com/post/167585735597/javascriptpro-redux-vs-mobx-which-is-best-for';
    }

    public function getPostTags()
    {
        return array();
    }

    public function getSmallThumbnail()
    {
        return "https://78.media.tumblr.com/dfe3a92e74d0cd8cf316b66d486ba507/tumblr_ozjn1m1Yhi1wzbngmo1_500.jpg";
    }

    public function getMediumThumbnail()
    {
        return "https://78.media.tumblr.com/dfe3a92e74d0cd8cf316b66d486ba507/tumblr_ozjn1m1Yhi1wzbngmo1_1280.jpg";
    }

    public function getThumbnail()
    {
        return $this->getSmallThumbnail();
    }

    public function getProcessingImages()
    {
        return array(
            new ProcessingImage($this->getThumbnail()),
            new ProcessingImage($this->getMediumThumbnail()),
            new ProcessingImage($this->getSmallThumbnail()),
        );
    }

}