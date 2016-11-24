<?php

namespace Tests\ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\LinkResolver;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Resolution;

class LinkResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testResolveValidUrlWithRedirections()
    {

        $target = 'http://bit.ly/VN34RV';
        $resolved = 'http://instagram.com/p/JXcPW9r2LD/';

        $return = new Resolution();
        $return->setStartingUrl($target);
        $return->setFinalUrl($resolved);
        $return->setStatusCode(200);

        $client = $this->getMockBuilder('Goutte\Client')->getMock();

        $client
            ->expects($this->once())
            ->method('request')
            ->will(
                $this->returnCallback(
                    function () use ($resolved) {

                        $crawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

                        $crawler->expects($this->any())
                            ->method('filterXPath')
                            ->will($this->returnSelf());
                        $crawler->expects($this->any())
                            ->method('attr')
                            ->will($this->returnValue($resolved));

                        return $crawler;
                    }
                )
            );

        $client
            ->expects($this->once())
            ->method('getResponse')
            ->will(
                $this->returnCallback(
                    function () use($resolved) {

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

        $link = new PreprocessedLink($target);
        $this->assertEquals($return, $linkResolver->resolve($link));

    }

    public function testResolveValidUrlWithCanonical()
    {

        $target = 'http://bit.ly/VN34RV';
        $resolved = 'http://instagr.am/p/JXcPW9r2LD/';
        $canonical = 'http://instagram.com/p/JXcPW9r2LD/';

        $return = new Resolution();
        $return->setStartingUrl($target);
        $return->setFinalUrl($canonical);
        $return->setStatusCode(200);

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

        $link = new PreprocessedLink($target);
        $this->assertEquals($return, $linkResolver->resolve($link));
    }

    public function testResolveValidUrlWithRelativeCanonical()
    {

        $target = 'http://bit.ly/1LPQl45';
        $resolved = 'https://vimeo.com/channels/staffpicks/120559169';
        $relativeCanonical = '/120559169';
        $canonical = 'https://vimeo.com/120559169';

        $return = new Resolution();
        $return->setStartingUrl($target);
        $return->setFinalUrl($canonical);
        $return->setStatusCode(200);

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

        $link = new PreprocessedLink($target);
        $this->assertEquals($return, $linkResolver->resolve($link));
    }

    public function testResolve404Url()
    {

        $target = 'http://bit.ly/VN34RV8';

        $return = new Resolution();
        $return->setStartingUrl($target);
        $return->setFinalUrl(null);
        $return->setStatusCode(404);

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

        $linkResolver = new LinkResolver($client);

        $link = new PreprocessedLink($target);
        $this->assertEquals($return, $linkResolver->resolve($link));
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
                        $exception = $this->getMockBuilder('ApiConsumer\Exception\CouldNotResolveException')
                            ->disableOriginalConstructor()
                            ->getMock();
                        throw $exception;
                    }
                )
            );
        $this->setExpectedException('ApiConsumer\Exception\CouldNotResolveException', 'Could not resolve url '.$target);

        $linkResolver = new LinkResolver($client);

        $link = new PreprocessedLink($target);
        $linkResolver->resolve($link);
    }
}