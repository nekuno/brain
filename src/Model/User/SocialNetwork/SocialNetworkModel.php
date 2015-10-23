<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User\SocialNetwork;

use Model\Neo4j\GraphManager;
use Model\Parser\BaseParser;
use Psr\Log\LoggerInterface;

/**
 * Class SocialNetworkModel
 *
 * @package Model
 */
abstract class SocialNetworkModel
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

    abstract protected function get($profileUrl, LoggerInterface $logger = null);
}
