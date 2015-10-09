<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\Parser;

use Goutte\Client;

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

    abstract public function parse($url);
}