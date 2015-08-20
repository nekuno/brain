<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp;

use GuzzleHttp\Client;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Class LookUp
 * @package Service
 */
abstract class LookUp
{
    protected $client;
    protected $apiKey;

    function __construct(Client $client, $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    abstract protected function getTypes();

    public function get($lookUpType, $value)
    {
        $this->validateType($lookUpType);
        $this->validateValue($lookUpType, $value);

        $response = $this->getFromClient($this->client, $lookUpType, $value, $this->apiKey);
        return $this->processData($response);
    }

    protected function validateType($lookUpType)
    {
        if(! in_array($lookUpType, $this->getTypes())) {
            throw new RuntimeException($lookUpType . ' type is not valid');
        }
    }

    protected function getFromClient(Client $client, $lookUpType, $value, $apiKey)
    {
        try {
            $response = $client->get('', array('query' => array(
                $lookUpType => $value,
                'apiKey' => $apiKey,
            )));
            if($response->getStatusCode() == 202) {
                throw new RuntimeException('Resource not available yet. Wait 2 minutes and execute the command again.');
            }
        } catch(Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return $response->json();
    }

    abstract protected function processData($response);

    abstract protected function processSocialData($response);

    abstract protected function validateValue($lookUpType, $value);
}