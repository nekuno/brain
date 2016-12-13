<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\TwitterProcessor\TwitterTweetProcessor;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;

class TwitterTweetProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TwitterResourceOwner|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceOwner;

    /**
     * @var TwitterUrlParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var TwitterTweetProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->resourceOwner = $this->getMockBuilder('ApiConsumer\ResourceOwner\TwitterResourceOwner')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder('ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser')
            ->getMock();

        $this->processor = new TwitterTweetProcessor($this->resourceOwner, $this->parser);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->setExpectedException('ApiConsumer\Exception\CannotProcessException', 'Could not process url ' . $url);

        $this->parser->expects($this->once())
            ->method('getStatusId')
            ->will($this->throwException(new UrlNotValidException($url)));

        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->requestItem($link);
    }

    /**
     * @dataProvider getStatusForRequestItem
     */
    public function testRequestItem($url, $id, $status)
    {
        $this->parser->expects($this->once())
            ->method('getStatusId')
            ->will($this->returnValue($id));

        $this->resourceOwner->expects($this->once())
            ->method('requestStatus')
            ->will($this->returnValue($status));

        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->requestItem($link);

        $this->assertEquals($url, $link->getUrl(), 'Asserting correct track response for ' . $url);
    }

    /**
     * @dataProvider getStatusForRequestItemWithEmbedded
     */
    public function testRequestItemWithEmbedded($url, $id, $status, $newUrl)
    {
        $this->parser->expects($this->once())
            ->method('getStatusId')
            ->will($this->returnValue($id));

        $this->resourceOwner->expects($this->once())
            ->method('requestStatus')
            ->will($this->returnValue($status));

        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->requestItem($link);

        $this->assertEquals($newUrl, $link->getUrl(), 'Asserting correct url extraction from tweet ' . $url);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $response, $expectedArray)
    {
        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->hydrateLink($link, $response);

        $this->assertEquals($expectedArray, $link->getLink()->toArray(), 'Asserting correct hydrated link for ' . $url);
    }

    /**
     * @dataProvider getResponseTags
     */
    public function testAddTags($url, $response, $expectedTags)
    {
        $link = new PreprocessedLink($url);
        $link->setUrl($url);
        $this->processor->addTags($link, $response);

        $tags = $expectedTags;
        sort($tags);
        $resultTags = $link->getLink()->getTags();
        sort($resultTags);
        $this->assertEquals($tags, $resultTags);
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
                $this->getStatusResponse(),
            )
        );
    }

    public function getStatusForRequestItemWithEmbedded()
    {
        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusId(),
                $this->getStatusResponseWithEmbedded(),
                $this->getNewUrl(),
            )
        );
    }

    public function getResponseHydration()
    {
        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusResponse(),
                array(
                    'title' => null,
                    'description' => null,
                    'thumbnail' => null,
                    'url' => null,
                    'id' => null,
                    'tags' => array(),
                    'created' => null,
                    'processed' => true,
                    'language' => null,
                    'synonymous' => array(),
                )
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getStatusUrl(),
                $this->getStatusResponse(),
                $this->getStatusTags(),
            )
        );
    }

    public function getStatusResponse()
    {
        return array(
            "created_at" => "Mon Oct 03 11=>45=>08 +0000 2016",
            "id" => 782909345961050100,
            "id_str" => "782909345961050112",
            "text" => "#TodasGamersJuegaAlMM Un juego de ligue donde mi mayor preocupación es que no estoy recibiendo los mails que espero. Realismo máximo.",
            "truncated" => false,
            "entities" => array(
                "hashtags" => array(
                    array(
                        "text" => "TodasGamersJuegaAlMM",
                        "indices" => array(
                            0,
                            21
                        )
                    )
                ),
                "symbols" => [],
                "user_mentions" => [],
                "urls" => [],
            ),
            "source" => "<a href='http://twitter.com' rel='nofollow'>Twitter Web Client</a>",
            "in_reply_to_status_id" => null,
            "in_reply_to_status_id_str" => null,
            "in_reply_to_user_id" => null,
            "in_reply_to_user_id_str" => null,
            "in_reply_to_screen_name" => null,
            "user" => array(
                "id" => 34529134,
                "id_str" => "34529134",
                "name" => "yawmoght",
                "screen_name" => "yawmoght",
                "location" => "",
                "description" => "Tool developer & data junkie",
                "url" => null,
                "entities" => array(
                    "description" => array(
                        "urls" => []
                    )
                ),
                "protected" => false,
                "followers_count" => 274,
                "friends_count" => 650,
                "listed_count" => 24,
                "created_at" => "Thu Apr 23 04=>17=>29 +0000 2009",
                "favourites_count" => 3369,
                "utc_offset" => 7200,
                "time_zone" => "Madrid",
                "geo_enabled" => true,
                "verified" => false,
                "statuses_count" => 2400,
                "lang" => "es",
                "contributors_enabled" => false,
                "is_translator" => false,
                "is_translation_enabled" => false,
                "profile_background_color" => "C0DEED",
                "profile_background_image_url" => "http://pbs.twimg.com/profile_background_images/364366364/Tardis_background.JPG",
                "profile_background_image_url_https" => "https=>//pbs.twimg.com/profile_background_images/364366364/Tardis_background.JPG",
                "profile_background_tile" => true,
                "profile_image_url" => "http://pbs.twimg.com/profile_images/639462703858380800/ZxusSbUW_normal.png",
                "profile_image_url_https" => "https=>//pbs.twimg.com/profile_images/639462703858380800/ZxusSbUW_normal.png",
                "profile_banner_url" => "https=>//pbs.twimg.com/profile_banners/34529134/1452345615",
                "profile_link_color" => "0084B4",
                "profile_sidebar_border_color" => "FFFFFF",
                "profile_sidebar_fill_color" => "DDEEF6",
                "profile_text_color" => "333333",
                "profile_use_background_image" => true,
                "has_extended_profile" => false,
                "default_profile" => false,
                "default_profile_image" => false,
                "following" => false,
                "follow_request_sent" => false,
                "notifications" => false
            ),
            "geo" => null,
            "coordinates" => null,
            "place" => null,
            "contributors" => null,
            "is_quote_status" => false,
            "retweet_count" => 1,
            "favorite_count" => 3,
            "favorited" => false,
            "retweeted" => false,
            "lang" => "es",
        );
    }

    public function getStatusResponseWithEmbedded()
    {
        return array(
            "created_at" => "Tue Oct 04 08:31:36 +0000 2016",
            "id" => 783223027786022900,
            "id_str" => "783223027786022912",
            "text" => "Nuevos usos para 'viejas' herramientas https://t.co/yMfIazbu9z",
            "truncated" => false,
            "entities" => array(
                "hashtags" => array(),
                "symbols" => array(),
                "user_mentions" => array(),
                "urls" => array(
                    array(
                        "url" => "https://t.co/yMfIazbu9z",
                        "expanded_url" => "http://www.nature.com/news/democratic-databases-science-on-github-1.20719",
                        "display_url" => "nature.com/news/democrati…",
                        "indices" => array(
                            39,
                            62
                        )
                    )
                )
            ),
            "source" => "<a href='http://twitter.com' rel='nofollow'>Twitter Web Client</a>",
            "in_reply_to_status_id" => null,
            "in_reply_to_status_id_str" => null,
            "in_reply_to_user_id" => null,
            "in_reply_to_user_id_str" => null,
            "in_reply_to_screen_name" => null,
            "user" => array(
                "id" => 34529134,
                "id_str" => "34529134",
                "name" => "yawmoght",
                "screen_name" => "yawmoght",
                "location" => "",
                "description" => "Tool developer & data junkie",
                "url" => null,
                "entities" => array(
                    "description" => array(
                        "urls" => array()
                    )
                ),
                "protected" => false,
                "followers_count" => 274,
                "friends_count" => 650,
                "listed_count" => 24,
                "created_at" => "Thu Apr 23 04:17:29 +0000 2009",
                "favourites_count" => 3370,
                "utc_offset" => 7200,
                "time_zone" => "Madrid",
                "geo_enabled" => true,
                "verified" => false,
                "statuses_count" => 2382,
                "lang" => "es",
                "contributors_enabled" => false,
                "is_translator" => false,
                "is_translation_enabled" => false,
                "profile_background_color" => "C0DEED",
                "profile_background_image_url" => "http://pbs.twimg.com/profile_background_images/364366364/Tardis_background.JPG",
                "profile_background_image_url_https" => "https://pbs.twimg.com/profile_background_images/364366364/Tardis_background.JPG",
                "profile_background_tile" => true,
                "profile_image_url" => "http://pbs.twimg.com/profile_images/639462703858380800/ZxusSbUW_normal.png",
                "profile_image_url_https" => "https://pbs.twimg.com/profile_images/639462703858380800/ZxusSbUW_normal.png",
                "profile_banner_url" => "https://pbs.twimg.com/profile_banners/34529134/1452345615",
                "profile_link_color" => "0084B4",
                "profile_sidebar_border_color" => "FFFFFF",
                "profile_sidebar_fill_color" => "DDEEF6",
                "profile_text_color" => "333333",
                "profile_use_background_image" => true,
                "has_extended_profile" => false,
                "default_profile" => false,
                "default_profile_image" => false,
                "following" => false,
                "follow_request_sent" => false,
                "notifications" => false
            ),
            "geo" => null,
            "coordinates" => null,
            "place" => null,
            "contributors" => null,
            "is_quote_status" => false,
            "retweet_count" => 0,
            "favorite_count" => 0,
            "favorited" => false,
            "retweeted" => false,
            "possibly_sensitive" => false,
            "lang" => "es"
        );
    }

    public function getNewUrl()
    {
        return 'http://www.nature.com/news/democratic-databases-science-on-github-1.20719';
    }

    public function getStatusUrl()
    {
        return 'https://twitter.com/yawmoght/status/782909345961050112';
    }

    public function getStatusId()
    {
        return '782909345961050112';
    }

    public function getStatusTags()
    {
        return array();
    }

}