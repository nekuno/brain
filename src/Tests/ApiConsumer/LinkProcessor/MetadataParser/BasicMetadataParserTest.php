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
     * @dataProvider getTitleTagTextData
     */
    public function testGetTitleTagText($expected, $title)
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('text')
            ->will($this->returnValue($title));

        $parser = new BasicMetadataParser();
        $method = new \ReflectionMethod($parser, 'getTitleTagText');
        $method->setAccessible(true);
        $actual = $method->invoke($parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTitleTagTextWithoutNode()
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('text')
            ->will($this->throwException(new \InvalidArgumentException()));

        $parser = new BasicMetadataParser();
        $method = new \ReflectionMethod($parser, 'getTitleTagText');
        $method->setAccessible(true);
        $actual = $method->invoke($parser, $crawler);

        $this->assertEquals(null, $actual);
    }

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
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->returnValue($description));

        $parser = new BasicMetadataParser();
        $method = new \ReflectionMethod($parser, 'getMetaDescriptionText');
        $method->setAccessible(true);
        $actual = $method->invoke($parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

    public function testGetMetaDescriptionTextWithoutNode()
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->once())
            ->method('filterXPath')
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->once())
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->throwException(new \InvalidArgumentException()));

        $parser = new BasicMetadataParser();
        $method = new \ReflectionMethod($parser, 'getMetaDescriptionText');
        $method->setAccessible(true);
        $actual = $method->invoke($parser, $crawler);

        $this->assertEquals(null, $actual);
    }

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

        $author = 'My author';
        $title = 'My title';
        $description = 'My description';

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->any())
            ->method('filterXPath')
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->at(3))
            ->method('text')
            ->will($this->returnValue($title));
        $crawler
            ->expects($this->exactly(2))
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->onConsecutiveCalls($author, $description));

        $parser = new BasicMetadataParser();

        $actual = $parser->extractMetadata($crawler);

        $this->assertNotEmpty($actual);
        $this->assertArrayHasKey('title', $actual);
        $this->assertArrayHasKey('author', $actual);
        $this->assertArrayHasKey('description', $actual);
        $this->assertEquals($author, $actual['author']);
        $this->assertEquals($title, $actual['title']);
        $this->assertEquals($description, $actual['description']);

    }

    public function testExtractMetadataWithoutNodes()
    {

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
        $crawler
            ->expects($this->any())
            ->method('filterXPath')
            ->withAnyParameters()
            ->will($this->returnSelf());
        $crawler
            ->expects($this->any())
            ->method('text')
            ->will($this->throwException(new \InvalidArgumentException));
        $crawler
            ->expects($this->any())
            ->method('attr')
            ->with($this->equalTo('content'))
            ->will($this->throwException(new \InvalidArgumentException));

        $parser = new BasicMetadataParser();

        $actual = $parser->extractMetadata($crawler);

        $this->assertNotEmpty($actual);
        $this->assertArrayHasKey('title', $actual);
        $this->assertArrayHasKey('author', $actual);
        $this->assertArrayHasKey('description', $actual);
        $this->assertEquals(null, $actual['author']);
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

        $parser = new BasicMetadataParser();
        $getMetaDescriptionTextMethod = new \ReflectionMethod($parser, 'extractTags');
        $getMetaDescriptionTextMethod->setAccessible(true);

        $actual = $getMetaDescriptionTextMethod->invoke($parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

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

    public function testExtractTagsWithoutNodes()
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

        $parser = new BasicMetadataParser();
        $getMetaDescriptionTextMethod = new \ReflectionMethod($parser, 'extractTags');
        $getMetaDescriptionTextMethod->setAccessible(true);

        $actual = $getMetaDescriptionTextMethod->invoke($parser, $crawler);

        $this->assertEquals($expected, $actual);
    }

}
