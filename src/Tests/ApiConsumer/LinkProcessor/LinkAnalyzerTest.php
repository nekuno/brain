<?php

namespace Tests\ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\LinkAnalyzer;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class LinkAnalyzerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param string $url The url from youtube
     * @dataProvider getYoutubeUrls
     */
    public function testYoutubeUrl($url)
    {
        $analyzer = new LinkAnalyzer();
        $this->assertEquals(LinkAnalyzer::YOUTUBE, $analyzer->getProcessorName(array('url' => $url)));
    }

    /**
     * @param string $url The url from spotify
     * @dataProvider getSpotifyUrls
     */
    public function testSpotifyUrl($url)
    {
        $analyzer = new LinkAnalyzer();
        $this->assertEquals(LinkAnalyzer::SPOTIFY, $analyzer->getProcessorName(array('url' => $url)));
    }

    /**
     * @param string $url The url from spotify
     * @dataProvider getUrls
     */
    public function testScrapperUrl($url)
    {
        $analyzer = new LinkAnalyzer();
        $this->assertEquals(LinkAnalyzer::SCRAPPER, $analyzer->getProcessorName(array('url' => $url)));
    }

    /**
     * @return array
     */
    public function getYoutubeUrls()
    {
        return array(
            array('http://youtube.com/v/dQw4w9WgXcQ?feature=youtube_gdata_player'),
            array('http://youtube.com/vi/dQw4w9WgXcQ?feature=youtube_gdata_player'),
            array('http://youtube.com/?v=dQw4w9WgXcQ&feature=youtube_gdata_player'),
            array('http://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player'),
            array('http://youtube.com/?vi=dQw4w9WgXcQ&feature=youtube_gdata_player'),
            array('http://youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player'),
            array('http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player'),
            array('http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player'),
        );
    }

    /**
     * @return array
     */
    public function getSpotifyUrls()
    {
        return array(
            array('http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf'),
            array('http://open.spotify.com/album/00m9T7kq5EyN6g3gEzgTQN'),
            array('http://open.spotify.com/artist/0Y5ldP4uHArYLgHdljfmAu'),
        );
    }

    /**
     * @return array
     */
    public function getUrls()
    {
        return array(
            array('http://google.es/'),
            array('http://www.amazon.com/'),
            array('http://www.nekuno.com/'),
        );
    }
}