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

    /**
     * @param $video
     * @param $id
     * @param $type
     * @dataProvider getVideos
     */
    public function testVideo($video, $id, $type)
    {
        $this->assertEquals($id, $this->parser->getYoutubeIdFromUrl($video), 'Extracting id ' . $id . ' from ' . $video);
        $this->assertEquals($type, $this->parser->getUrlType($video), 'Asserting that ' . $video . ' is a video');
    }

    /**
     * @param $channel
     * @param $id
     * @param $type
     * @dataProvider getChannels
     */
    public function testChannel($channel, $id, $type)
    {
        $this->assertEquals($id, $this->parser->getChannelIdFromUrl($channel), 'Extracting id ' . $id . ' from ' . $channel);
        $this->assertEquals($type, $this->parser->getUrlType($channel), 'Asserting that ' . $channel . ' is a channel');
    }

    /**
     * @param $playlist
     * @param $id
     * @param $type
     * @dataProvider getPlaylists
     */
    public function testPlaylist($playlist, $id, $type)
    {
        $this->assertEquals($id, $this->parser->getPlaylistIdFromUrl($playlist), 'Extracting id ' . $id . ' from ' . $playlist);
        $this->assertEquals($type, $this->parser->getUrlType($playlist), 'Asserting that ' . $playlist . ' is a playlist');
    }

    /**
     * @return array
     */
    public function getVideos()
    {

        return array(
            array('http://www.google.es', false, false),
            array('http://youtube.com/v/dQw4w9WgXcQ?feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
            array('http://youtube.com/vi/dQw4w9WgXcQ?feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
            array('http://youtube.com/?v=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
            array('http://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
            array('http://youtube.com/?vi=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
            array('http://youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
            array('http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
            array('http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player', 'dQw4w9WgXcQ', YoutubeUrlParser::VIDEO_URL),
        );
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return array(
            array('http://www.google.es', false, false),
            array('https://www.youtube.com/channel/UCSi3NhHZWE7xXAs2NDDAxDg', 'UCSi3NhHZWE7xXAs2NDDAxDg', YoutubeUrlParser::CHANNEL_URL),
        );
    }

    /**
     * @return array
     */
    public function getPlaylists()
    {
        return array(
            array('http://www.google.es', false, false),
            array('https://www.youtube.com/view_play_list?p=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E', YoutubeUrlParser::PLAYLIST_URL),
            array('https://www.youtube.com/view_play_list?list=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E', YoutubeUrlParser::PLAYLIST_URL),
            array('https://www.youtube.com/playlist?p=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E', YoutubeUrlParser::PLAYLIST_URL),
            array('https://www.youtube.com/playlist?list=PL55713C70BA91BD6E', 'PL55713C70BA91BD6E', YoutubeUrlParser::PLAYLIST_URL),
        );
    }
}