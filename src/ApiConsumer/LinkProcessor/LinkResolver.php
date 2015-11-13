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

    /**
     * @param $url
     * @return null|string
     */
    public function resolve($url)
    {

        try {
            $crawler = $this->client->request('GET', $url);
            $statusCode = $this->client->getResponse()->getStatus();
        } catch (RequestException $e) {
            return $this->client->getRequest()->getUri();
        } catch (\LogicException $e) {
            return $url;
        }

        $uri = $this->client->getRequest()->getUri();

        if ($statusCode == 200) {

            try {
                $canonical = $crawler->filterXPath('//link[@rel="canonical"]')->attr('href');
            } catch (\InvalidArgumentException $e) {
                $canonical = null;
            }

            if ($canonical && $uri !== $canonical) {

                $canonical = $this->verifyCanonical($canonical, $uri);

                return $canonical;
            }

        }

        if ($statusCode >= 400){
            return null;
        }

        return $uri;

    }

    protected function verifyCanonical($canonical, $uri)
    {
        $parsedCanonical = parse_url($canonical);

        if (!isset($parsedCanonical['scheme']) && !isset($parsedCanonical['host'])) {
            $parsedUri = parse_url($uri);
            $parsedCanonical['scheme'] = $parsedUri['scheme'];
            $parsedCanonical['host'] = $parsedUri['host'];
            $canonical = http_build_url($parsedCanonical);
        }

        return $canonical;
    }
} 