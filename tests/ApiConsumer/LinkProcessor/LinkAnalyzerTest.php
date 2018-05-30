<?php

namespace Tests\ApiConsumer\LinkProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Link\Link;
use PHPUnit\Framework\TestCase;

class LinkAnalyzerTest extends TestCase
{

    /**
     * @dataProvider getUrlsForProcessorName
     */
    public function testProcessorName($url, $expectedProcessorName)
    {
        $link = new PreprocessedLink($url);
        $link->setUrl($url);

        $processorName = LinkAnalyzer::getProcessorName($link);
        $this->assertEquals($expectedProcessorName, $processorName, 'Asserting that processor type for ' . $url . ' is ' . $processorName);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadProcessorName($url)
    {
        $link = new PreprocessedLink($url);

        $type = LinkAnalyzer::getProcessorName($link);

        $this->assertEquals(UrlParser::SCRAPPER, $type);
    }

    /**
     * @dataProvider getUrlsForMustResolve
     */
    public function testMustResolve($url, $expectedMustResolve)
    {
        $link = new PreprocessedLink($url);

        $mustResolve = LinkAnalyzer::mustResolve($link);

        $this->assertEquals($expectedMustResolve, $mustResolve);
    }

    /**
     * @dataProvider getTextForIsTextSimilar
     */
    public function testIsTextSimilar($text1, $text2, $expectedIsSimilar)
    {
        $isSimilar = LinkAnalyzer::isTextSimilar($text1, $text2);
        $this->assertEquals($expectedIsSimilar, $isSimilar, 'Asserting that '.$text1.' and '.$text2.' are similar');
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url'),
        );
    }

    //TODO: Add fringe cases
    public function getUrlsForProcessorName()
    {
        return array(
            array('http://www.google.com', UrlParser::SCRAPPER),
            array('http://www.twitter.com/nekuno', TwitterUrlParser::TWITTER_PROFILE),
            array('http://www.twitter.com/intent?screen_name=nekuno', TwitterUrlParser::TWITTER_INTENT),
            array('http://pic.twitter.com/identifier', TwitterUrlParser::TWITTER_PIC),
            array('https://twitter.com/BigDataBlogs/status/724263095334359041?lang=es', TwitterUrlParser::TWITTER_TWEET),
            array('https://www.youtube.com/user/elrubiusOMG?&ab_channel=elrubiusOMG', YoutubeUrlParser::CHANNEL_URL),
            array('https://www.youtube.com/watch?v=8P5jCIIj8WI', YoutubeUrlParser::VIDEO_URL),
            array('https://www.youtube.com/playlist?list=PLutfYmbsCnJjF2rBl2Wxdc1tBbiRQvcW9', YoutubeUrlParser::PLAYLIST_URL),
            array('http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf', SpotifyUrlParser::TRACK_URL),
            array('https://open.spotify.com/album/02dncVzAWXSAI3e8oJnoug/6BEocXyX92zZ2vLc4yxewo', SpotifyUrlParser::ALBUM_TRACK_URL),
            array('http://open.spotify.com/album/4sb0eMpDn3upAFfyi4q2rw', SpotifyUrlParser::ALBUM_URL),
            array('http://open.spotify.com/artist/4Ww5mwS7BWYjoZTUIrMHfC', SpotifyUrlParser::ARTIST_URL),
            array('http://www.facebook.com/vips', FacebookUrlParser::FACEBOOK_PAGE),
            array('https://www.facebook.com/roberto.m.pallarola', FacebookUrlParser::FACEBOOK_PAGE),
        );
    }

    public function getUrlsForMustResolve()
    {
        return array(
            array('http://www.twitter.com/nekuno', true),
            array('http://open.spotify.com/track/1g9PysFSHeHjVcACqwduNf', false),
        );
    }

    public function getTextForIsTextSimilar()
    {
        return array(
            array('abcdefghij', 'abcdefghij', true),
            array('abcdefghij', 'abcdefg', true),
            array('abcdefghij', 'abdeghi', true),
            array('abcdefghij', 'a', false),
            array('Alejandro', 'Bad Romance', false),
            array('Alejandro by Lady Gaga', 'Bad Romance by Lady Gaga', true),
        );
    }
}