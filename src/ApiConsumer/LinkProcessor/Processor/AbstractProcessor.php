<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\UrlParser\UrlParserInterface;
use GuzzleHttp\Client;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use Service\UserAggregator;

abstract class AbstractProcessor implements ProcessorInterface
{

    protected $userAggregator;

    /** @var  ResourceOwnerInterface */
    protected $resourceOwner;

    /** @var  $parser UrlParserInterface */
    protected $parser;

    protected $scraperProcessor;

	protected $client;

    public function __construct(UserAggregator $userAggregator, ScraperProcessor $scraperProcessor, ResourceOwnerInterface $resourceOwner, UrlParserInterface $urlParser, Client $client)
    {
        $this->userAggregator = $userAggregator;
        $this->scraperProcessor = $scraperProcessor;
	    $this->resourceOwner = $resourceOwner;
	    $this->parser = $urlParser;
	    $this->client = $client;
    }

    protected function addCreator($username)
    {
        $this->userAggregator->addUser($username, $this->resourceOwner->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getParser()
    {
        return $this->parser;
    }

	protected function getClient() {
		return $this->client;
	}

    public function isCorrectResponse($url)
    {
        $response = $this->getClient()->head($url);
        if (200 <= $response->getStatusCode() && $response->getStatusCode() < 300 && strpos($response->getHeader('Content-Type'), 'image') !== false ){
            return true;
        }

        return false;
    }
}