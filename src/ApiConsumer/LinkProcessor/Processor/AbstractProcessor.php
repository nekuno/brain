<?php

namespace ApiConsumer\LinkProcessor\Processor;


use Http\OAuth\ResourceOwner\ResourceOwnerInterface;
use Service\UserAggregator;

abstract class AbstractProcessor implements ProcessorInterface
{

    protected $userAggregator;

    /** @var  ResourceOwnerInterface */
    protected $resourceOwner;

    public function __construct(UserAggregator $userAggregator)
    {
        $this->userAggregator = $userAggregator;
    }

    protected function addCreator($username)
    {
        $this->userAggregator->addUser($username, $this->resourceOwner->getName());
    }
}