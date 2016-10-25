<?php

namespace ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;

abstract class AbstractFacebookProcessor extends AbstractProcessor
{
    /**
     * @var FacebookResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var FacebookUrlParser
     */
    protected $parser;

    //TODO: Move to Link? Can be done without dependency?
    protected function buildTitleFromDescription(array $response)
    {
        if (!isset($response['description'])){
            return null;
        }
        $description = $response['description'];

        return strlen($description) >= 25 ? mb_substr($description, 0, 22) . '...' : $description;
    }

    protected function buildDescriptionFromTitle(array $response)
    {
        return isset($response['name'])? $response['name'] : null;
    }
}