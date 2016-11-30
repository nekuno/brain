<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace Tests\ApiConsumer\LinkProcessor\MetadataParser;

use ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser;

class FacebookMetadataParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var FacebookMetadataParser
     */
    private $parser;

    public function setUp()
    {

        $this->parser = new FacebookMetadataParser();
    }

    /**
     * @dataProvider getExtractOgTitleTextData
     */
    public function testExtractOgTitleText($expected, $testData)
    {

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//meta[@property="og:title"]')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with('content')
            ->will($this->returnValue($testData));

        $method = new \ReflectionMethod(
            '\ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser',
            'getOgTitleText'
        );
        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

    public function testExtractOgTitleTextWhenIsNotExists()
    {

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//meta[@property="og:title"]')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with('content')
            ->will($this->throwException(new \InvalidArgumentException()));

        $method = new \ReflectionMethod(
            '\ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser',
            'getOgTitleText'
        );

        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals(null, $actual);
    }

    /**
     * @dataProvider getExtractOgDescriptionTextData
     */
    public function testExtractOgDescriptionText($expected, $testData)
    {

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//meta[@property="og:description"]')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with('content')
            ->will($this->returnValue($testData));

        $method = new \ReflectionMethod(
            '\ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser',
            'getOgDescriptionText'
        );

        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

    public function testExtractOgDescriptionTextWhenIsNotExists()
    {

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//meta[@property="og:description"]')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with('content')
            ->will($this->throwException(new \InvalidArgumentException()));

        $method = new \ReflectionMethod(
            '\ApiConsumer\LinkProcessor\MetadataParser\FacebookMetadataParser',
            'getOgDescriptionText'
        );

        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals(null, $actual);
    }

    /**
     * @dataProvider getExtractMetadataData
     */
    public function testExtractMetadata($expected, $testData)
    {

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->any())
            ->method('filterXPath')
            ->with($this->stringStartsWith('//meta[@property'))
            ->will($this->returnSelf());
        $crawler
            ->expects($this->any())
            ->method('attr')
            ->with('content')
            ->will($this->onConsecutiveCalls($testData['title'], $testData['description'], $testData['language']));

        $actual = $this->parser->extractMetadata($crawler);

        $this->assertTrue(is_array($actual));
        $this->assertEquals($expected, $actual);
    }

    public function testExtractTags()
    {

        $testData = array('Tag', 'Other tag', 'A long tag');
        $expected = array(array('name' => 'Tag'), array('name' => 'Other tag'));

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with($this->stringStartsWith('//meta[@property'))
            ->will($this->returnSelf());
        $crawler
            ->expects($this->any())
            ->method('attr')
            ->with('content');
        $crawler
            ->expects($this->once())
            ->method('each')
            ->will(
                $this->returnValue(
                    array(array('name' => $testData[0]), array('name' => $testData[1]), array('name' => $testData[2]))
                )
            );

        $actual = $this->parser->extractTags($crawler);

        $this->assertTrue(is_array($actual));
        $this->assertEquals($expected, $actual);
    }

    public function testExtractTagsWhenIsNotExists()
    {

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with($this->stringStartsWith('//meta[@property'))
            ->will($this->returnSelf());

        $actual = $this->parser->extractTags($crawler);

        $this->assertTrue(is_array($actual));
        $this->assertEmpty($actual);
    }

    public function getExtractOgTitleTextData()
    {

        return array(
            array('FB title', 'FB title'),
            array(null, ''),
            array(null, '   '),
            array(null, null)
        );
    }

    public function getExtractOgDescriptionTextData()
    {

        return array(
            array('Description for test', 'Description for test'),
            array(null, ''),
            array(null, '   '),
            array(null, null)
        );
    }

    public function getExtractMetadataData()
    {

        return array(
            array(
                array(
                    'title' => 'My title',
                    'description' => 'Test description',
                    'language' => 'es',
                    'thumbnail' => null,
                ),
                array(
                    'title' => 'My title',
                    'description' => 'Test description',
                    'language' => 'es',
                    'thumbnail' => null,
                ),
            ),
            array(
                array(
                    'title' => null,
                    'description' => null,
                    'language' => null,
                    'thumbnail' => null,
                ),
                array(
                    'title' => ' ',
                    'description' => ' ',
                    'language' => '',
                ),
            ),
        );
    }
}
