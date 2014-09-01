<?php

namespace ApiConsumer\LinkProcessor;

use Goutte\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class LinkResolver
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    public function resolve($url, array $visited = array())
    {

        try {
            $crawler = $this->client->request('GET', $url);
            $statusCode = $this->client->getResponse()->getStatus();
        } catch (RequestException $e) {
            return $this->client->getRequest()->getUri();
        }

        if (!in_array($url, $visited)) {
            $visited[] = $url;
        }

        if ($statusCode == 200) {

            try {
                $canonical = $crawler->filterXPath('//link[@rel="canonical"]')->attr('href');
            } catch (\InvalidArgumentException $e) {
                $canonical = null;
            }

            $uri = $this->client->getRequest()->getUri();

            if ($canonical && $uri !== $canonical) {

                if (in_array($uri, $visited)) {
                    return $canonical;
                }

                $visited[] = $uri;

                return $this->resolve($canonical, $visited);
            }

            return $uri;
        }

        return $this->client->getRequest()->getUri();

    }
} 