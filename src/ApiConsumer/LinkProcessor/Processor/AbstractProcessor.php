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

    public function __construct(UserAggregator $userAggregator)
    {
        $this->userAggregator = $userAggregator;
    }

    protected function addCreator($username)
    {
        $this->userAggregator->addUser($username, $this->resourceOwner->getName());
    }

    public function getParser()
    {
        return $this->parser;
    }
}