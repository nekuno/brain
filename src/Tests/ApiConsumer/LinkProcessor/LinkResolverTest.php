<?php

namespace Tests\ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\LinkResolver;
use MyProject\Proxies\__CG__\stdClass;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class LinkResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testRevolveValidUrlWithRedirections()
    {

        $target = 'http://bit.ly/VN34RV';
        $resolved = 'http://instagram.com/p/JXcPW9r2LD/';

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

        $crawler->expects($this->any())
            ->method('filterXPath')
            ->will($this->returnSelf());
        $crawler->expects($this->any())
            ->method('attr')
            ->will($this->returnValue(null));

        $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')->getMock();

        $response->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue(200));

        $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->once())
            ->method('getUri')
            ->will($this->returnValue($resolved));

        $client = $this->getMockBuilder('Goutte\Client')->getMock();
        $client
            ->expects($this->once())
            ->method('request')
            ->will($this->returnValue($crawler));
        $client
            ->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));
        $client
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($resolved, $linkResolver->resolve($target));

    }
} 