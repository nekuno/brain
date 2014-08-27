<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace Tests\ApiConsumer\LinkProcessor\MetadataParser;

use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;

/**
 * Class BasicMetadataParserTest
 * @package Tests\ApiConsumer\LinkProcessor\MetadataParser
 */
class BasicMetadataParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var BasicMetadataParser
     */
    private $parser;

    public function setUp()
    {

        $this->parser = new BasicMetadataParser();
    }

    /**
     * @dataProvider getTitleTagTextData
     */
    public function testGetTitleTagText($expected, $title)
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//title')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('text')
            ->will($this->returnValue($title));

        $method = new \ReflectionMethod($this->parser, 'getTitleTagText');
        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTitleTagTextWhenIsNotExists()
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//title')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('text')
            ->will($this->throwException(new \InvalidArgumentException()));

        $method = new \ReflectionMethod($this->parser, 'getTitleTagText');
        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals(null, $actual);
    }

    /**
     * @return array
     */
    public function getTitleTagTextData()
    {

        return array(
            array('My title', 'My title'),
            array(null, ''),
            array(null, ' '),
            array(null, null)
        );

    }

    /**
     * @dataProvider getMetaDescriptionTextData
     */
    public function testGetMetaDescriptionText($expected, $description)
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//meta[@name="description"]')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->returnValue($description));

        $method = new \ReflectionMethod($this->parser, 'getMetaDescriptionText');
        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

    public function testGetMetaDescriptionTextWhenIsNotExists()
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->with('//meta[@name="description"]')
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->throwException(new \InvalidArgumentException()));

        $method = new \ReflectionMethod($this->parser, 'getMetaDescriptionText');
        $method->setAccessible(true);
        $actual = $method->invoke($this->parser, $crawler);

        $this->assertEquals(null, $actual);
    }

    /**
     * @return array
     */
    public function getMetaDescriptionTextData()
    {

        return array(
            array('My description', 'My description'),
            array(null, ''),
            array(null, ' '),
            array(null, null)
        );

    }

    public function testExtractMetadata()
    {

        $title = 'My title';
        $description = 'My description';

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->any())
            ->method('filterXPath')
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->at(1))
            ->method('text')
            ->will($this->returnValue($title));
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->onConsecutiveCalls($description));

        $actual = $this->parser->extractMetadata($crawler);

        $this->assertNotEmpty($actual);
        $this->assertArrayHasKey('title', $actual);
        $this->assertArrayHasKey('description', $actual);
        $this->assertEquals($title, $actual['title']);
        $this->assertEquals($description, $actual['description']);

    }

    public function testExtractMetadataWhenIsNotExists()
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->any())
            ->method('filterXPath')
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('text')
            ->will($this->throwException(new \InvalidArgumentException));
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->throwException(new \InvalidArgumentException));

        $actual = $this->parser->extractMetadata($crawler);

        $this->assertNotEmpty($actual);
        $this->assertArrayHasKey('title', $actual);
        $this->assertArrayHasKey('description', $actual);
        $this->assertEquals(null, $actual['title']);
        $this->assertEquals(null, $actual['description']);

    }

    /**
     * @dataProvider testExtractTagsData
     */
    public function testExtractTags($expected, $testData)
    {

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->exactly(1))
            ->method('filterXPath')
            ->with($this->equalTo('//meta[@name="keywords"]'))
            ->will($this->returnSelf());
        $crawler
            ->expects($this->exactly(1))
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->returnValue($testData));

        $getMetaDescriptionTextMethod = new \ReflectionMethod($this->parser, 'extractTags');
        $getMetaDescriptionTextMethod->setAccessible(true);

        $actual = $getMetaDescriptionTextMethod->invoke($this->parser, $crawler);

        $this->assertTrue(is_array($actual));
        $this->assertCount(count($expected), $actual);
    }

    /**
     * @return array
     */
    public function testExtractTagsData()
    {

        return array(
            array(
                array(
                    array('name' => 'tag1'),
                    array('name' => 'tag2'),
                    array('name' => 'java'),
                    array('name' => 'other'),
                    array('name' => 'two words')
                ),
                'Tag1, tAg2, java, Other, One two three four, two words',
            ),
            array(
                array(),
                '',
            ),
            array(
                array(),
                '  ',
            ),
            array(
                array(),
                null,
            ),
        );
    }

    public function testExtractTagsWhenIsNotExists()
    {

        $expected = array();

        $crawler = $this->getMockBuilder('\Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->exactly(1))
            ->method('filterXPath')
            ->with($this->equalTo('//meta[@name="keywords"]'))
            ->will($this->returnSelf());
        $crawler
            ->expects($this->exactly(1))
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->throwException(new \InvalidArgumentException));

        $getMetaDescriptionTextMethod = new \ReflectionMethod($this->parser, 'extractTags');
        $getMetaDescriptionTextMethod->setAccessible(true);

        $actual = $getMetaDescriptionTextMethod->invoke($this->parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

}
