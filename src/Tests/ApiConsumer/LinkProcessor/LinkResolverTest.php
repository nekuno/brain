<?php

namespace Tests\ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\LinkResolver;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class LinkResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testRevolveValidUrlWithRedirections()
    {

        $target = 'http://bit.ly/VN34RV';
        $resolved = 'http://instagram.com/p/JXcPW9r2LD/';

        $client = $this->getMockBuilder('Goutte\Client')->getMock();

        $client
            ->expects($this->once())
            ->method('request')
            ->will($this->returnCallback(function () {

                $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

                $crawler->expects($this->any())
                    ->method('filterXPath')
                    ->will($this->returnSelf());
                $crawler->expects($this->any())
                    ->method('attr')
                    ->will($this->returnValue(null));

                return $crawler;
            }));

        $client
            ->expects($this->once())
            ->method('getResponse')
            ->will($this->returnCallback(function () {

                $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')->getMock();

                $response->expects($this->once())
                    ->method('getStatus')
                    ->will($this->returnValue(200));

                return $response;
            }));

        $client
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnCallback(function () use ($resolved) {

                $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

                $request->expects($this->once())
                    ->method('getUri')
                    ->will($this->returnValue($resolved));

                return $request;
            }));

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($resolved, $linkResolver->resolve($target));

    }

    public function testResolveValidUrlWithCanonical()
    {

        $target = 'http://bit.ly/VN34RV';
        $resolved = 'http://instagr.am/p/JXcPW9r2LD/';
        $canonical = 'http://instagram.com/p/JXcPW9r2LD/';

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

        $crawler->expects($this->exactly(2))
            ->method('filterXPath')
            ->will($this->returnSelf());
        $crawler->expects($this->exactly(2))
            ->method('attr')
            ->will($this->onConsecutiveCalls($canonical, null));

        $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')->getMock();

        $response->expects($this->exactly(2))
            ->method('getStatus')
            ->will($this->returnValue(200));

        $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->exactly(2))
            ->method('getUri')
            ->will($this->onConsecutiveCalls($resolved, $canonical));

        $client = $this->getMockBuilder('Goutte\Client')->getMock();

        $client
            ->expects($this->exactly(2))
            ->method('request')
            ->will($this->returnValue($crawler));

        $client
            ->expects($this->exactly(2))
            ->method('getResponse')
            ->will($this->returnValue($response));

        $client
            ->expects($this->exactly(2))
            ->method('getRequest')
            ->will($this->returnValue($request));

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($canonical, $linkResolver->resolve($target));
    }

    public function testResolve404Url()
    {

        $target = 'http://bit.ly/VN34RV';

        $client = $this->getMockBuilder('Goutte\Client')->getMock();

        $client
            ->expects($this->once())
            ->method('getResponse')
            ->will($this->returnCallback(function () {

                $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')
                    ->getMock();

                $response->expects($this->once())
                    ->method('getStatus')
                    ->will($this->returnValue(404));

                return $response;
            }));

        $client
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnCallback(function () use ($target) {

                $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

                $request->expects($this->once())
                    ->method('getUri')
                    ->will($this->returnValue($target));

                return $request;
            }));

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($target, $linkResolver->resolve($target));
    }

    public function testResolveTimeoutUrl()
    {

        $target = 'http://bit.ly/VN34RV';

        $client = $this->getMockBuilder('Goutte\Client')->getMock();

        $client
            ->expects($this->once())
            ->method('request')
            ->will($this->returnCallback(function () {
                $exception = $this->getMockBuilder('GuzzleHttp\Exception\RequestException')
                    ->disableOriginalConstructor()
                    ->getMock();
                throw $exception;
            }));

        $client
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnCallback(function () use ($target) {

                $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
                    ->disableOriginalConstructor()
                    ->getMock();

                $request->expects($this->once())
                    ->method('getUri')
                    ->will($this->returnValue($target));

                return $request;
            }));

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($target, $linkResolver->resolve($target));
    }
}