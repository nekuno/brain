<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\Exception\CouldNotResolveException;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

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

    public function resolve(PreprocessedLink $preprocessedLink)
    {
        $resolution = new Resolution();
        $resolution->setStartingUrl($preprocessedLink->getFetched());

        try {

            $this->fixUrlHost($resolution);
            $this->uglyQuickFix($resolution);

            if ($this->client->getHistory()) {
                $this->client->getHistory()->clear();
            }

            $crawler = $this->client->request('GET', $resolution->getStartingUrl());

            $resolution->setStatusCode($this->client->getResponse()->getStatus());
            if ($resolution->isCorrect()) {

                $canonical = $this->getCanonical($crawler);
                $canonical = $this->verifyCanonical($canonical);

                $resolution->setFinalUrl($canonical);
            }

        } catch (\Exception $e) {
            throw new CouldNotResolveException($preprocessedLink->getFetched());
        }

        return $resolution;

    }

    public function isCorrectImageResponse($url)
    {
        $response = $this->client->getClient()->head($url);
        if (200 <= $response->getStatusCode() && $response->getStatusCode() < 300 && strpos($response->getHeader('Content-Type'), 'image') !== false) {
            return true;
        }

        return false;
    }

    protected function verifyCanonical($canonical)
    {
        $uri = $this->client->getRequest()->getUri();

        if ($canonical && $uri !== $canonical) {

            $parsedCanonical = parse_url($canonical);

            if (!isset($parsedCanonical['scheme']) && !isset($parsedCanonical['host'])) {
                $parsedUri = parse_url($uri);
                $parsedCanonical['scheme'] = $parsedUri['scheme'];
                $parsedCanonical['host'] = $parsedUri['host'];
                $canonical = http_build_url($parsedCanonical);
            }
        }

        return $canonical;
    }

    private function fixUrlHost(Resolution $resolution)
    {
        if (!parse_url($resolution->getStartingUrl(), PHP_URL_HOST)) {
            $resolution->setStartingUrl('http://' . $resolution->getStartingUrl());
        };
    }

    private function uglyQuickFix(Resolution $resolution)
    {
        /* TODO: Remove this quick fix, put here because of Curl not firing error 52 (empty response) */
        $host = parse_url($resolution->getStartingUrl(), PHP_URL_HOST);
        $firstLetter = substr($host, 0, 1);
        if (strtoupper($firstLetter) == $firstLetter || $host == 'imprint.printmag.com') {
            throw new \Exception('This url would not return data');
        }
        /* End of quick fix */
    }

    private function getCanonical(Crawler $crawler)
    {
        try {
            $canonical = $crawler->filterXPath('//link[@rel="canonical"]')->attr('href');
        } catch (\InvalidArgumentException $e) {
            $canonical = $this->client->getRequest()->getUri();
        }

        return $canonical;
    }
} 