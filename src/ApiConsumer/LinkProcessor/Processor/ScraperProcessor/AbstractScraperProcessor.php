<?php

namespace ApiConsumer\LinkProcessor\Processor\ScraperProcessor;

use ApiConsumer\Factory\GoutteClientFactory;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use Goutte\Client;

abstract class AbstractScraperProcessor implements ProcessorInterface
{
    /**
     * @var GoutteClientFactory
     */
    protected $clientFactory;
    /**
     * @var Client
     */
    protected $client;

    /**
     * AbstractScraperProcessor constructor.
     * @param GoutteClientFactory $goutteClientFactory
     */
    public function __construct(GoutteClientFactory $goutteClientFactory)
    {
        $this->clientFactory = $goutteClientFactory;
        $this->client = $this->clientFactory->build();
    }

    public function getSynonymousParameters(PreprocessedLink $preprocessedLink, array $data)
    {
        return new SynonymousParameters();
    }

    public function getResourceOwner()
    {
        return null;
    }

    public function isLinkWorking($url)
    {
        return true;
    }
}