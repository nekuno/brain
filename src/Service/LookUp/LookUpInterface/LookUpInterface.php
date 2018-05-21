<?php

namespace Service\LookUp\LookUpInterface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface LookUpInterface
{
    function __construct($apiUrl, $apiKey, UrlGeneratorInterface $urlGenerator);

    public function get($lookUpType, $value, $id);

    public function getProcessedResponse($response);
}