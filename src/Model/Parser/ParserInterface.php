<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\Parser;

use Goutte\Client;

interface ParserInterface
{
    public function __construct(Client $client);

    public function parse($url);
}