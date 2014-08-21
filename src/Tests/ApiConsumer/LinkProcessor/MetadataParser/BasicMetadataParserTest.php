<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace Tests\ApiConsumer\LinkProcessor\MetadataParser;


use ApiConsumer\LinkProcessor\MetadataParser\BasicMetadataParser;

class BasicMetadataParserTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var BasicMetadataParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new BasicMetadataParser();

    }

    public function testExtractMetadataOfAnEmptyArrayReturnsSame(){
        $testData = array();
        $actual = $this->parser->extractMetadata($testData);

        $this->assertEmpty($actual);
    }

    public function testExtractMetadataOfAnArrayWithEveryThingNullReturnsEmpty(){
        $testData = array(
            array('rel' => null, 'name' => null, 'content' => null),
            array('rel' => null, 'name' => null, 'content' => null, 'property' => null),
        );
        $actual = $this->parser->extractMetadata($testData);

        $this->assertEmpty($actual);
    }

}
