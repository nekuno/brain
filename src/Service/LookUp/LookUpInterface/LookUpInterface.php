<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp\LookUpInterface;

use GuzzleHttp\Client;
use Symfony\Component\Routing\Generator\UrlGenerator;

interface LookUpInterface
{
    function __construct(Client $client, $apiKey, UrlGenerator $urlGenerator);

    public function get($lookUpType, $value, $id);

    public function getProcessedResponse($response);

    // TODO: Disable web hook by now (refactoring needed)
    //public function mergeFromWebHook(LookUpData $lookUpData, $data);
}