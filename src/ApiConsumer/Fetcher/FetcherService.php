<?php

namespace ApiConsumer\Fetcher;

use Monolog\Logger;
use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Registry\Registry;
use ApiConsumer\LinkProcessor\LinkResolver;
use ApiConsumer\Storage\StorageInterface;

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
     * @var LinkResolver
     */
    protected $linkResolver;

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
        LinkResolver $linkResolver,
        StorageInterface $storage,
        \Closure $getResourceOwnerByName,
        array $options)
    {
        $this->logger = $logger;
        $this->userProvider = $userProvider;
        $this->registry = $registry;
        $this->linkResolver = $linkResolver;
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

    public function fetch($userId, $fetcherName)
    {
        $links = array();
        try {
            $this->logger->info(sprintf('Fetch attempt for user %d, fetcher %s', $userId, $fetcherName));

            $fetcher = $this->getFetcherByName($fetcherName);
            $resource = $fetcher->getResourceOwnerName();
            $user = $this->userProvider->getUsersByResource($resource, $userId);

            if ($user) {

                $links = $fetcher->fetchLinksFromUserFeed($user);

                foreach ($links as $key => $link) {
                    $url = $this->linkResolver->resolve($link['url']);
                    if ($url) {
                        $links[$key]['url'] = $url;
                    }
                }

                $this->storage->storeLinks($user['id'], $links);
                foreach ($this->storage->getErrors() as $error) {
                    $this->logger->error(sprintf('Error saving link: ' . $error));
                }

                $numLinks = count($links);
                if ($numLinks) {
                    $lastItemId = $links[$numLinks - 1]['resourceItemId'];
                } else {
                    $lastItemId = null;
                }

                $this->registry->registerFetchAttempt(
                    $user['id'],
                    $resource,
                    $lastItemId,
                    false
                );
            }
        } catch (\Exception $e) {
            $this->logger->addError(sprintf('Error fetching from resource %s', $resource));
            $this->logger->addError(sprintf('%s', $e->getMessage()));

            $this->registry->registerFetchAttempt(
                $user['id'],
                $resource,
                null,
                true
            );
            throw new \Exception('Error fetching ' . $fetcherName . ' for user ' . $userId, 1);
        }

        return $links;
    }

    private function getFetcherByName($name)
    {
        if (isset($this->options[$name])) {
            $options = $this->options[$name];
        } else {
            throw new \Exception('Error fetcher ' . $name . ' not found', 1);
        }

        $fetcherClass = $options['class'];

        $resourceOwnerName = $options['resourceOwner'];
        $resourceOwner = $this->getResourceOwnerByName($resourceOwnerName);

        $fetcher = new $fetcherClass($resourceOwner);

        return $fetcher;
    }
}