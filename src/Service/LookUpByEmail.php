<?php

namespace Service;

use GuzzleHttp\Client;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\Security\Acl\Exception\Exception;

class LookUpByEmail
{
    private $fullContactClient;
    private $fullContactApiKey;
    private $peopleGraphClient;
    private $peopleGraphApiKey;

    function __construct(Client $fullContactClient, $fullContactApiKey, Client $peopleGraphClient, $peopleGraphApiKey)
    {
        $this->fullContactClient = $fullContactClient;
        $this->fullContactApiKey = $fullContactApiKey;
        $this->peopleGraphClient = $peopleGraphClient;
        $this->peopleGraphApiKey = $peopleGraphApiKey;
    }

    public function getFromFullContact($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("email not valid");
        }

        $fullContactResponse = $this->getFromClient($this->fullContactClient, $email, $this->fullContactApiKey);
        $fullContactProcessedData = $this->processFullContactData($fullContactResponse);

        return $fullContactProcessedData;
    }

    public function getFromPeopleGraph($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("email not valid");
        }

        $peopleGraphResponse = $this->getFromClient($this->peopleGraphClient, $email, $this->peopleGraphApiKey);
        $peopleGraphProcessedData = $this->processPeopleGraphData($peopleGraphResponse);

        return $peopleGraphProcessedData;
    }


    private function getFromClient(Client $client, $email, $apiKey)
    {
        try {
            $response = $client->get('', array('query' => array(
                'email' => $email,
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

    private function processFullContactData($response)
    {
        $socialData = array();
        if(is_array($response) && isset($response['socialProfiles']) && ! empty($response['socialProfiles'])) {
            foreach($response['socialProfiles'] as $socialProfile) {
                $socialData[$socialProfile['type']] = $socialProfile['url'];
            }
        }

        return $socialData;
    }

    private function processPeopleGraphData($response)
    {
        $socialData = array();
        if(is_array($response) && isset($response['result']['profiles']) && ! empty($response['result']['profiles'])) {
            foreach($response['result']['profiles'] as $socialProfile) {
                $socialData[$socialProfile['type']] = $socialProfile['url'];
            }
        }

        return $socialData;
    }
}