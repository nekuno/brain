<?php

namespace Model\Parser;

use Goutte\Client;
use Psr\Log\LoggerInterface;

interface ParserInterface
{
    public function __construct(Client $client);

    public function parse($url, LoggerInterface $logger);
}