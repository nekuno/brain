<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp\LookUpInterface;

use GuzzleHttp\Client;
use Model\Entity\LookUpData;

interface LookUpInterface
{
    function __construct(Client $client, $apiKey);

    public function getTypes();

    public function get($lookUpType, $value);

    public function merge(LookUpData $lookUpData1, LookUpData $lookUpData2);
}