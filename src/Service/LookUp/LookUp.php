<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp;

use GuzzleHttp\Client;
use Model\Exception\ValidationException;

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
            $exception = new ValidationException('Validation errors');
            $exception->setErrors(array($lookUpType . ' type is not valid'));
            throw $exception;
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
                // TODO: Should get data from web hook
                //throw new Exception('Resource not available yet. Wait 2 minutes and execute the command again.', 202);
            }
            if($response->getStatusCode() == 200) {
                return $response->json();
            }
        } catch(\Exception $e) {
            // TODO: Refuse exceptions by now
            return array();
            //throw new Exception($e->getMessage(), 404);
        }

        return array();
    }

    abstract protected function processData($response);

    abstract protected function processSocialData($response);

    abstract protected function validateValue($lookUpType, $value);
}