<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;


class ScraperProcessorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getProcessData
     */
    public function testProcess($expected, $metadata, $tags, $link)
    {

//        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
//
//        $crawler->expects($this->any())
//            ->method('filterXPath')
//            ->will($this->returnSelf());
//        $crawler->expects($this->any())
//            ->method('text');
//        $crawler->expects($this->any())
//            ->method('attr');
//        $crawler->expects($this->any())
//            ->method('each');
//
//        $response = $this->getMockBuilder('\Symfony\Component\BrowserKit\Response')->getMock();
//
//        $client = $this->getMockBuilder('\Goutte\Client')->getMock();
//        $client
//            ->expects($this->once())
//            ->method('request')
//            ->will($this->returnValue($crawler));
//        $client
//            ->expects($this->any())
//            ->method('getResponse')
//            ->will($this->returnValue($response));
//
//        $basicMetadataParser = $this->getMockBuilder(
//            '\ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser'
//        )->getMock();
//
//        $basicMetadataParser
//            ->expects($this->once())
//            ->method('extractMetadata')
//            ->with($crawler)
//            ->will($this->returnValue($metadata));
//        $basicMetadataParser
//            ->expects($this->once())
//            ->method('extractTags')
//            ->with($crawler)
//            ->will($this->returnValue($tags));
//
//        $fbMetadataParser = $this->getMockBuilder(
//            '\ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser'
//        )->getMock();
//
//        $fbMetadataParser
//            ->expects($this->once())
//            ->method('extractMetadata')
//            ->with($crawler)
//            ->will($this->returnValue($metadata));
//        $fbMetadataParser
//            ->expects($this->once())
//            ->method('extractTags')
//            ->with($crawler)
//            ->will($this->returnValue($tags));
//
//        $scraper = new ScraperProcessor($client, $basicMetadataParser, $fbMetadataParser);
//
//        $actual = $scraper->process($link);
//
//        $this->assertEquals($expected, $actual);

    }

    public function getProcessData()
    {

        return array(
            array(
                array(
                    'url' => 'http://fake.com',
                    'title' => 'My title',
                    'description' => 'My description',
                    'tags' => array(
                        array('name' => 'tag1'),
                        array('name' => 'tag2'),
                    ),
                ),
                array(
                    'url' => 'http://fake.com',
                    'title' => '',
                    'description' => 'My description',
                ),
                array(
                    array('name' => 'tag1'),
                    array('name' => 'tag2'),
                ),
                array(
                    'url' => 'http://fake.com',
                    'title' => 'My title',
                ),
            ),
            array(
                array(
                    'url' => 'http://fake.com',
                    'title' => 'My title',
                    'description' => 'My description',
                    'tags' => array(),
                ),
                array(
                    'url' => 'http://fake.com',
                    'title' => 'My title',
                    'description' => 'My description',
                ),
                array(),
                array(
                    'url' => 'http://fake.com',
                    'title' => 'My title',
                    'description' => 'Before description',
                ),
            ),
            array(
                array(
                    'url' => 'http://fake.com',
                    'title' => 'Before title',
                    'description' => 'Before description',
                    'tags' => array(),
                ),
                array(
                    'url' => null,
                    'title' => null,
                    'description' => null,
                ),
                array(),
                array(
                    'url' => 'http://fake.com',
                    'title' => 'Before title',
                    'description' => 'Before description',
                ),
            ),
        );
    }

}
