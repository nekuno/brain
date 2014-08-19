<?php

namespace Tests\ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class YoutubeUrlParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var YoutubeUrlParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new YoutubeUrlParser();
    }

    public function testTypeIsFalseWhenBadUrlFormat()
    {
        $url = 'This is not an url';
        $this->assertEquals(false, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid format');
    }

    public function testTypeIsFalseWhenNotYoutubeUrl()
    {
        $url = 'http://www.google.es';
        $this->assertEquals(false, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid');
    }

    public function testTypeIsFalseWhenUrlIsNotAValidType()
    {
        $url = 'http://www.youtube.com/notvalidpath/dQw4w9WgXcQ';
        $this->assertEquals(false, $this->parser->getUrlType($url), 'Asserting that ' . $url . ' is not valid');
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
        $this->assertEquals($id, $this->parser->getYoutubeIdFromUrl($url), 'Extracting id ' . $id . ' from ' . $url);
    }

    /**
     * @param string $url The url of the channel
     * @param string $id The id of the channel
     * @dataProvider getChannels
     */
    public function testGetIdFromChannelUrl($url, $id)
    {
        $this->assertEquals($id, $this->parser->getChannelIdFromUrl($url), 'Extracting id ' . $id . ' from ' . $url);
    }

    /**
     * @param string $url The url of the playlist
     * @param string $id The id of the playlist
     * @dataProvider getPlaylists
     */
    public function testGetIdFromPlaylistUrl($url, $id)
    {
        $this->assertEquals($id, $this->parser->getPlaylistIdFromUrl($url), 'Extracting id ' . $id . ' from ' . $url);
    }

    /**
     * @return array
     */
    public function getVideos()
    {

        return array(
            array('http://youtube.com/v/dQw4w9WgXcQ?feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
            array('http://youtube.com/vi/dQw4w9WgXcQ?feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
            array('http://youtube.com/?v=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
            array('http://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
            array('http://youtube.com/?vi=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
            array('http://youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
            array('http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
            array('http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player', 'dQw4w9WgXcQ'),
        );
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return array(
            array('https://www.youtube.com/channel/UCSi3NhHZWE7xXAs2NDDAxDg', 'UCSi3NhHZWE7xXAs2NDDAxDg'),
        );
    }

    /**
     * @return array
     */
    public function getPlaylists()
    {
        return array(
            array('https://www.youtube.com/view_play_list?p=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E'),
            array('https://www.youtube.com/view_play_list?list=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E'),
            array('https://www.youtube.com/playlist?p=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E'),
            array('https://www.youtube.com/playlist?list=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E'),
        );
    }
}