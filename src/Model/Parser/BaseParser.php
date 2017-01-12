<?php

namespace Model\Parser;

use Goutte\Client;
use Psr\Log\LoggerInterface;

abstract class BaseParser implements ParserInterface
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    abstract public function parse($url, LoggerInterface $logger = null);
}