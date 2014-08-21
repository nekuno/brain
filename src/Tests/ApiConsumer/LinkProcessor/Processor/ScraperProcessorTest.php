<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace Tests\ApiConsumer\LinkProcessor\Processor;


use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;

class ScraperProcessorTest extends \PHPUnit_Framework_TestCase {


    public function testInstantiation()
    {
        $client = $this->getMockBuilder('Goutte\Client')->getMock();
        $parserInterface = $this->getMockBuilder('\ApiConsumer\LinkProcessor\MetadataParser\MetadataParserInterface')->getMock();
        new ScraperProcessor($client, $parserInterface, $parserInterface);
    }

}
