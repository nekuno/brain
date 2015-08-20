<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp\LookUpInterface;

use GuzzleHttp\Client;

interface LookUpInterface
{
    function __construct(Client $client, $apiKey);

    public function getTypes();

    public function get($lookUpType, $value);
}