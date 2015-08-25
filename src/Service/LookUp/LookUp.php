<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp;

use GuzzleHttp\Client;
use Model\Entity\LookUpData;
use Model\Exception\ValidationException;
use Service\LookUp\LookUpInterface\LookUpInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class LookUp
 * @package Service
 */
abstract class LookUp implements LookUpInterface
{
    protected $client;
    protected $apiKey;
    protected $urlGenerator;

    function __construct(Client $client, $apiKey, UrlGenerator $urlGenerator)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->urlGenerator = $urlGenerator;
    }

    abstract protected function getTypes();

    public function get($lookUpType, $value, $id = null)
    {
        $this->validateType($lookUpType);
        $this->validateValue($lookUpType, $value);

        $response = $this->getFromClient($this->client, $lookUpType, $value, $this->apiKey, $id);

        return $this->toObject($this->processData($response));
    }

    protected function validateType($lookUpType)
    {
        if(! in_array($lookUpType, $this->getTypes())) {
            $exception = new ValidationException('Validation errors');
            $exception->setErrors(array($lookUpType . ' type is not valid'));
            throw $exception;
        }
    }

    protected function getFromClient(Client $client, $lookUpType, $value, $apiKey, $webHookId)
    {
        try {
            if($webHookId) {
                $route = $this->urlGenerator->generate('setLookUpFromWebHook', array(), UrlGenerator::ABSOLUTE_URL);
                $query = array(
                    $lookUpType => $value,
                    'apiKey' => $apiKey,
                    'webHookUrl' => urlencode($route),
                    'webHookId' => $webHookId,
                );
            } else {
                $query = array(
                    $lookUpType => $value,
                    'apiKey' => $apiKey,
                );
            }
            $response = $client->get('', array('query' => $query));
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

    public function mergeFromWebHook(LookUpData $lookUpData, $data)
    {
        $newLookUpData = $this->toObject($this->processData($data));
        $lookUpData = $this->merge($lookUpData, $newLookUpData);
        return $lookUpData;
    }

    abstract protected function processData($response);

    abstract protected function processSocialData($response);

    abstract protected function validateValue($lookUpType, $value);

    protected function toObject($lookUpData)
    {
        $lookUpDataObj = new LookUpData();

        if(isset($lookUpData['name'])) {
            $lookUpDataObj->setName($lookUpData['name']);
        }
        if(isset($lookUpData['email'])) {
            $lookUpDataObj->setEmail($lookUpData['email']);
        }
        if(isset($lookUpData['gender'])) {
            $lookUpDataObj->setGender($lookUpData['gender']);
        }
        if(isset($lookUpData['location'])) {
            $lookUpDataObj->setLocation($lookUpData['location']);
        }
        if(isset($lookUpData['socialProfiles'])) {
            $lookUpDataObj->setSocialProfiles($lookUpData['socialProfiles']);
        }

        return $lookUpDataObj;
    }

    public function merge(LookUpData $lookUpData1, LookUpData $lookUpData2)
    {
        if(! $lookUpData1->getName() && $lookUpData2->getName()) {
            $lookUpData1->setName($lookUpData2->getName());
        }
        if(! $lookUpData1->getEmail() && $lookUpData2->getEmail()) {
            $lookUpData1->setEmail($lookUpData2->getEmail());
        }
        if(! $lookUpData1->getGender() && $lookUpData2->getGender()) {
            $lookUpData1->setGender($lookUpData2->getGender());
        }
        if(! $lookUpData1->getLocation() && $lookUpData2->getLocation()) {
            $lookUpData1->setLocation($lookUpData2->getLocation());
        }

        $lookUpData1->addSocialProfiles($lookUpData2->getSocialProfiles());

        return $lookUpData1;
    }
}