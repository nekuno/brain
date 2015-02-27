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
            ->will(
                $this->returnCallback(
                    function () {

                        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

                        $crawler->expects($this->any())
                            ->method('filterXPath')
                            ->will($this->returnSelf());
                        $crawler->expects($this->any())
                            ->method('attr')
                            ->will($this->returnValue(null));

                        return $crawler;
                    }
                )
            );

        $client
            ->expects($this->once())
            ->method('getResponse')
            ->will(
                $this->returnCallback(
                    function () {

                        $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')->getMock();

                        $response->expects($this->once())
                            ->method('getStatus')
                            ->will($this->returnValue(200));

                        return $response;
                    }
                )
            );

        $client
            ->expects($this->once())
            ->method('getRequest')
            ->will(
                $this->returnCallback(
                    function () use ($resolved) {

                        $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
                            ->disableOriginalConstructor()
                            ->getMock();

                        $request->expects($this->once())
                            ->method('getUri')
                            ->will($this->returnValue($resolved));

                        return $request;
                    }
                )
            );

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($resolved, $linkResolver->resolve($target));

    }

    public function testResolveValidUrlWithCanonical()
    {

        $target = 'http://bit.ly/VN34RV';
        $resolved = 'http://instagr.am/p/JXcPW9r2LD/';
        $canonical = 'http://instagram.com/p/JXcPW9r2LD/';

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

        $crawler->expects($this->any())
            ->method('filterXPath')
            ->will($this->returnSelf());
        $crawler->expects($this->any())
            ->method('attr')
            ->will($this->returnValue($canonical));

        $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')->getMock();

        $response->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(200));

        $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue($resolved));

        $client = $this->getMockBuilder('Goutte\Client')->getMock();

        $client
            ->expects($this->any())
            ->method('request')
            ->will($this->returnValue($crawler));

        $client
            ->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $client
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($canonical, $linkResolver->resolve($target));
    }

    public function testResolveValidUrlWithRelativeCanonical()
    {

        $target = 'http://bit.ly/1LPQl45';
        $resolved = 'https://vimeo.com/channels/staffpicks/120559169';
        $relativeCanonical = '/120559169';
        $canonical = 'https://vimeo.com/120559169';

        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

        $crawler->expects($this->any())
            ->method('filterXPath')
            ->will($this->returnSelf());
        $crawler->expects($this->any())
            ->method('attr')
            ->will($this->returnValue($relativeCanonical));

        $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')->getMock();

        $response->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(200));

        $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue($resolved));

        $client = $this->getMockBuilder('Goutte\Client')->getMock();

        $client
            ->expects($this->any())
            ->method('request')
            ->will($this->returnValue($crawler));

        $client
            ->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $client
            ->expects($this->any())
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
            ->will(
                $this->returnCallback(
                    function () {

                        $response = $this->getMockBuilder('Symfony\Component\BrowserKit\Response')
                            ->getMock();

                        $response->expects($this->once())
                            ->method('getStatus')
                            ->will($this->returnValue(404));

                        return $response;
                    }
                )
            );

        $client
            ->expects($this->once())
            ->method('getRequest')
            ->will(
                $this->returnCallback(
                    function () use ($target) {

                        $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
                            ->disableOriginalConstructor()
                            ->getMock();

                        $request->expects($this->once())
                            ->method('getUri')
                            ->will($this->returnValue($target));

                        return $request;
                    }
                )
            );

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
            ->will(
                $this->returnCallback(
                    function () {
                        $exception = $this->getMockBuilder('GuzzleHttp\Exception\RequestException')
                            ->disableOriginalConstructor()
                            ->getMock();
                        throw $exception;
                    }
                )
            );

        $client
            ->expects($this->once())
            ->method('getRequest')
            ->will(
                $this->returnCallback(
                    function () use ($target) {

                        $request = $this->getMockBuilder('Symfony\Component\BrowserKit\Request')
                            ->disableOriginalConstructor()
                            ->getMock();

                        $request->expects($this->once())
                            ->method('getUri')
                            ->will($this->returnValue($target));

                        return $request;
                    }
                )
            );

        $linkResolver = new LinkResolver($client);

        $this->assertEquals($target, $linkResolver->resolve($target));
    }
}