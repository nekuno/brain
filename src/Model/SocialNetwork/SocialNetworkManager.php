<?php

namespace Model\SocialNetwork;

use Model\Neo4j\GraphManager;
use Model\Parser\BaseParser;
use Psr\Log\LoggerInterface;


abstract class SocialNetworkManager
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var BaseParser
     */
    protected $parser;

    public function __construct(GraphManager $gm, BaseParser $parser)
    {
        $this->gm = $gm;
        $this->parser = $parser;
    }

    abstract public function set($id, $profileUrl, LoggerInterface $logger = null);

    abstract public function getData($profileUrl, LoggerInterface $logger = null);
}
