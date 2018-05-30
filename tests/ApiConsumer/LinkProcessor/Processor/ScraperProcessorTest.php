<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Factory\GoutteClientFactory;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor\ScraperProcessor;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Model\Link\Link;

class ScraperProcessorTest extends AbstractProcessorTest
{
    /**
     * @var UrlParser
     */
    protected $parser;

    /**
     * @var ScraperProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->parser = new UrlParser();

        $goutteClientFactory = new GoutteClientFactory();
        $this->processor = new ScraperProcessor($goutteClientFactory, $this->brainBaseUrl);
    }

    /**
     * @dataProvider getBadUrls
     */
    public function testBadUrlRequestItem($url)
    {
        $this->expectException(UrlNotValidException::class);

        $this->parser->cleanURL($url);
    }

    /**
     * @dataProvider getResponseHydration
     */
    public function testHydrateLink($url, $expectedArray)
    {
        $url = $this->parser->cleanURL($url);
        $link = new PreprocessedLink($url);
        $link->getFirstLink()->setUrl($link->getUrl());
        $response = $this->processor->getResponse($link);
        $this->processor->hydrateLink($link, $response);
        $this->assertEquals($expectedArray, $link->getFirstLink()->toArray(), 'Asserting correct hydrated link for ' . $url);
    }

    /**
     * @dataProvider getResponseTags
     */
    public function testAddTags($url, $expectedTags)
    {
        $link = new PreprocessedLink($url);
        $response = $this->processor->getResponse($link);
        $this->processor->addTags($link, $response);

        $tags = $expectedTags;
        sort($tags);
        $resultTags = $link->getFirstLink()->getTags();
        sort($resultTags);
        $this->assertEquals($tags, $resultTags);
    }

    public function getBadUrls()
    {
        return array(
            array('this is not an url')
        );
    }

    public function getResponseHydration()
    {
        $expected = new Link();
        $expected->setTitle('¿De dónde proceden los pelirrojos? - ¡No sabes nada!');
        $expected->setDescription('El gen responsable del color rojizo del cabello ya se encontraba en los emigrantes africanos que decidieron explorar el resto del mundo hace 50.000 años.');
        $expected->setThumbnail('http://d2ruuu7iu87htj.cloudfront.net/uploads/2017/03/02204111/portada-pelirrojos-curiosidades-beqbe.jpg');
        $expected->setUrl('http://www.nosabesnada.com/otras-curiosidades/85357/de-donde-proceden-los-pelirrojos');
        $expected->setLanguage('es');
        return array(
            array(
                $this->getUrl(),
                $expected->toArray(),
            )
        );
    }

    public function getResponseTags()
    {
        return array(
            array(
                $this->getUrl(),
                $this->getTags(),
            )
        );
    }

    public function getUrl()
    {
        return 'http://www.nosabesnada.com/otras-curiosidades/85357/de-donde-proceden-los-pelirrojos/';
    }

    public function getTags()
    {
        return array(
            array('name' => 'cabello'),
            array('name' => 'pelirrojos')
        );
    }

}
