<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Registry\Registry;
use ApiConsumer\Storage\StorageInterface;
use Monolog\Logger;

class FetcherService
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var \Closure
     */
    protected $getResourceOwnerByName;

    /**
     * @var array
     */
    protected $options;

    public function __construct(
        Logger $logger,
        UserProviderInterface $userProvider,
        Registry $registry,
        StorageInterface $storage,
        \Closure $getResourceOwnerByName,
        array $options
    ) {

        $this->logger = $logger;
        $this->userProvider = $userProvider;
        $this->registry = $registry;
        $this->storage = $storage;
        $this->getResourceOwnerByName = $getResourceOwnerByName;
        $this->options = $options;
    }

    public function __call($method, $args)
    {

        if (is_callable(array($this, $method))) {
            return call_user_func_array($this->$method, $args);
        } else {
            throw new \Exception('Error ' . $method . ' not defined', 1);
        }
    }

    public function fetch($userId, $resourceOwner)
    {

        $links = array();
        try {
            $this->logger->info(sprintf('Fetch attempt for user %d, fetcherConfig %s', $userId, $resourceOwner));

            foreach ($this->options as $fetcherConfig) {
                if ($fetcherConfig['resourceOwner'] === $resourceOwner) {
                    $user = $this->userProvider->getUsersByResource($resourceOwner, $userId);
                    if (!$user) {
                        throw new \Exception('User not found');
                    }
                    /** @var FetcherInterface $fetcher */
                    $fetcher = new $fetcherConfig['class']($this->getResourceOwnerByName($resourceOwner));
                    $links = $fetcher->fetchLinksFromUserFeed($user);
                    $this->storage->storeLinks($user['id'], $links);
                    foreach ($this->storage->getErrors() as $error) {
                        $this->logger->error(sprintf('Error saving link: ' . $error));
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Error fetching ' . $resourceOwner . ' for user ' . $userId, 1);
        }

        return $links;
    }

}
