<?php

namespace ApiConsumer\Scraper;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Scraper
 *
 * @package ApiConsumer\Scraper
 */
class Scraper
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * @param $url
     * @param string $method
     * @return $this
     */
    public function initCrawler($url, $method = 'GET')
    {

        $this->crawler = $this->client->request($method, $url);

        return $this;
    }

    /**
     * @param $filter
     * @return Crawler
     */
    public function scrap($filter)
    {

        return $this->crawler->filterXPath($filter);
    }
}
