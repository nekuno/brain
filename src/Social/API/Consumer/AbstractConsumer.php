<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 1:30 PM
 */

namespace Social\API\Consumer;

use Social\API\Consumer\Auth\UserProviderInterface;
use Social\API\Consumer\Http\Client;
use Social\API\Consumer\Storage\StorageInterface;

class AbstractConsumer
{

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var Client
     */
    protected $httpConnector;

    public function __construct(StorageInterface $store, UserProviderInterface $userProvider, Client $Connector)
    {

        $this->storage = $store;

        $this->userProvider = $userProvider;

        $this->httpConnector = $Connector;

    }

    /**
     * @param $data
     * @return array
     * @throws \Exception
     */
    protected function processData($data)
    {
        $links = array();
        foreach ($data as $userId => $shared) {
            try {
                $parseLinks = $this->parseLinks($shared, $userId);
                $links      = $links + $parseLinks;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        try {
            $stored = $this->storage->storeLinks($links);

            return $stored;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * @param $data
     * @param $userId
     * @return array
     */
    protected function parseLinks($data, $userId)
    {
        return array();
    }

    /**
     * @param $e
     * @return string
     */
    protected function getError($e)
    {
        return sprintf('Error: %s on file %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
    }

} 