<?php

namespace ApiConsumer\LinkProcessor\Processor;


use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Http\OAuth\ResourceOwner\ResourceOwnerInterface;
use Service\UserAggregator;

abstract class AbstractProcessor implements ProcessorInterface
{

    protected $userAggregator;

    /** @var  ResourceOwnerInterface */
    protected $resourceOwner;

    /** @var  $parser UrlParser */
    protected $parser;

    protected $scraperProcessor;

    public function __construct(UserAggregator $userAggregator, ScraperProcessor $scraperProcessor)
    {
        $this->userAggregator = $userAggregator;
        $this->scraperProcessor = $scraperProcessor;
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
}