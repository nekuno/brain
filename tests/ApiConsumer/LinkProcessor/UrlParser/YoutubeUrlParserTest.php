<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use PHPUnit\Framework\TestCase;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeUrlParserTest extends TestCase
{

    /**
     * @var YoutubeUrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new YoutubeUrlParser();
    }

    /**
     * @param $url
     * @dataProvider getBadUrls
     */
    public function testBadUrls($url)
    {
        $this->expectException(UrlNotValidException::class);
        $url = $this->parser->cleanURL($url);
        $this->parser->getUrlType($url);
    }

    /**
     * @param string $url The url of the video
     * @dataProvider getVideos
     */
    public function testTypeIsVideo($url)
    {
        $this->assertEquals(YoutubeUrlParser::VIDEO_URL, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is a video');
    }

    /**
     * @param string $url The url of the channel
     * @dataProvider getChannels
     */
    public function testTypeIsChannel($url)
    {
        $this->assertEquals(YoutubeUrlParser::CHANNEL_URL, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is a channel');
    }

    /**
     * @param string $url The url of the playlist
     * @dataProvider getPlaylists
     */
    public function testTypeIsPlaylist($url)
    {
        $this->assertEquals(YoutubeUrlParser::PLAYLIST_URL, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is a playlist');
    }

    /**
     * @param string $url The url of the video
     * @param string $id The id of the video
     * @dataProvider getVideos
     */
    public function testGetIdFromVideoUrl($url, $id)
    {
        $this->assertEquals($id, $this->parser->getVideoId($url), 'Extracting id ' . reset($id) . ' from ' . $url);
    }

    /**
     * @param string $url The url of the channel
     * @param string $id The id of the channel
     * @dataProvider getChannels
     */
    public function testGetIdFromChannelUrl($url, $id)
    {
        $this->assertEquals($id, $this->parser->getChannelId($url), 'Extracting id ' . reset($id) . ' from ' . $url);
    }

    /**
     * @param string $url The url of the playlist
     * @param string $id The id of the playlist
     * @dataProvider getPlaylists
     */
    public function testGetIdFromPlaylistUrl($url, $id)
    {
        $this->assertEquals($id, $this->parser->getPlaylistId($url), 'Extracting id ' . reset($id) . ' from ' . $url);
    }

    /**
     * @dataProvider getUrlsForClean
     */
    public function testCleanUrl($url, $expectedCleanUrl)
    {
        $cleanUrl = $this->parser->cleanURL($url);

        $this->assertEquals($expectedCleanUrl, $cleanUrl, 'Cleaning url ' . $url . ' to ' . $cleanUrl);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url'),
            array('http://www.google.es'),
            array('http://www.youtube.com/notvalidpath/dQw4w9WgXcQ'),
        );
    }

    /**
     * @return array
     */
    public function getVideos()
    {
        return array(
            array('http://youtube.com/v/dQw4w9WgXcQ?feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
            array('http://youtube.com/vi/dQw4w9WgXcQ?feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
            array('http://youtube.com/?v=dQw4w9WgXcQ&feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
            array('http://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
            array('http://youtube.com/?vi=dQw4w9WgXcQ&feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
            array('http://youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
            array('http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
            array('http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player', array('id' => 'dQw4w9WgXcQ')),
        );
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return array(
            array('https://www.youtube.com/channel/UCSi3NhHZWE7xXAs2NDDAxDg', array('id' => 'UCSi3NhHZWE7xXAs2NDDAxDg')),
            array('https://www.youtube.com/user/RanguGamer?&ab_channel=RanguGamer', array('forUsername' => 'RanguGamer')),
        );
    }

    /**
     * @return array
     */
    public function getPlaylists()
    {
        return array(
            array('https://www.youtube.com/view_play_list?p=PL55713C70BA91BD6E', array('id' => 'PL55713C70BA91BD6E')),
            array('https://www.youtube.com/view_play_list?list=PL55713C70BA91BD6E', array('id' => 'PL55713C70BA91BD6E')),
            array('https://www.youtube.com/playlist?p=PL55713C70BA91BD6E', array('id' => 'PL55713C70BA91BD6E')),
            array('https://www.youtube.com/playlist?list=PL55713C70BA91BD6E', array('id' => 'PL55713C70BA91BD6E')),
        );
    }

    public function getUrlsForClean()
    {
        return array(
            array('https://www.youtube.com/watch?v=luo8M3u_WJI&list=PLutfYmbsCnJjF2rBl2Wxdc1tBbiRQvcW9&index=1&ab_channel=RanguGamer', 'https://www.youtube.com/watch?v=luo8M3u_WJI'),
        );
    }
}