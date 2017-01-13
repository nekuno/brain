<?php

namespace Service\LookUp\LookUpInterface;

use GuzzleHttp\Client;
use Symfony\Component\Routing\Generator\UrlGenerator;

interface LookUpInterface
{
    function __construct(Client $client, $apiKey, UrlGenerator $urlGenerator);

    public function get($lookUpType, $value, $id);

    public function getProcessedResponse($response);
}