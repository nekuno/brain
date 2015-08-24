<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp\LookUpInterface;

use GuzzleHttp\Client;
use Model\Entity\LookUpData;
use Symfony\Component\Routing\Generator\UrlGenerator;

interface LookUpInterface
{
    function __construct(Client $client, $apiKey, UrlGenerator $urlGenerator);

    public function getTypes();

    public function get($lookUpType, $value, $id);

    public function merge(LookUpData $lookUpData1, LookUpData $lookUpData2);

    public function mergeFromWebHook(LookUpData $lookUpData, $data);
}