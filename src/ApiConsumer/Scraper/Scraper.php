<?php

namespace ApiConsumer\Scraper;

use Goutte\Client;
use Monolog\Logger;

/**
 * Class Scraper
 * @package ApiConsumer\WebScraper
 */
class Scraper
{

    private $client;

    private $url;

    private $logger;

    /**
     * @param mixed $logger
     */
    public function setLogger(Logger $logger)
    {

        $this->logger = $logger;
    }

    public function __construct(Client $client, $url = null)
    {

        $this->client = $client;

        $this->url = $url;

    }

    /**
     * @return MetadataServer IP Whitelist
    83.59.176.5Remove84.124.227.43Remove79.151.34.164Remove54.195.225.42Remove88.1.74.78Remove88.12.7.99Remove92.222.1.98
     * @throws \Exception
     */
    public function scrap()
    {

        if (null === $this->url) {
            throw new \InvalidArgumentException('The URL can not be empty');
        }

        try {
            $crawler = $this->client
                ->request('GET', $this->url)
                ->filterXPath('//head/meta | //title');
        } catch (\Exception $e) {
            throw $e;
        }

        return new Metadata($crawler);

    }

    /**
     * @param $e
     * @return string
     */
    protected function getError(\Exception $e)
    {

        return sprintf('Error: %s on file %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
    }
}
