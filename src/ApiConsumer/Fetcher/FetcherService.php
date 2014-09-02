<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Event\MatchingEvent;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\Storage\StorageInterface;
use Http\OAuth\Factory\ResourceOwnerFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FetcherService implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var LinkProcessor
     */
    protected $linkProcessor;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var ResourceOwnerFactory
     */
    protected $resourceOwnerFactory;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $options;

    public function __construct(
        UserProviderInterface $userProvider,
        LinkProcessor $linkProcessor,
        StorageInterface $storage,
        ResourceOwnerFactory $resourceOwnerFactory,
        EventDispatcher $dispatcher,
        array $options
    )
    {

        $this->userProvider = $userProvider;
        $this->linkProcessor = $linkProcessor;
        $this->storage = $storage;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->dispatcher = $dispatcher;
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

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function fetch($userId, $resourceOwner)
    {

        $links = array();
        try {

            $this->logger->info(sprintf('Fetch attempt for user %d, fetcherConfig %s', $userId, $resourceOwner));

            foreach ($this->options as $service => $fetcherConfig) {
                if ($fetcherConfig['resourceOwner'] === $resourceOwner) {
                    $user = $this->userProvider->getUsersByResource($resourceOwner, $userId);
                    if (!$user) {
                        throw new \Exception('User not found');
                    }
                    /** @var FetcherInterface $fetcher */
                    $fetcher = new $fetcherConfig['class']($this->resourceOwnerFactory->build($resourceOwner));
                    $links = $fetcher->fetchLinksFromUserFeed($user);
                    foreach ($links as $key => $link) {
                        $links[$key] = $this->linkProcessor->process($link);
                    }
                    $this->storage->storeLinks($user['id'], $links);
                    foreach ($this->storage->getErrors() as $error) {
                        $this->logger->error(sprintf('Error saving link: %s', $error));
                    }

                    // Dispatch event for enqueue new matching re-calculate task
                    $data = array(
                        'userId' => $user['id'],
                        'service' => $service,
                        'type' => 'process',
                    );
                    $event = new MatchingEvent($data);
                    $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, $event);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    'Error fetching %s for user %d. Message: %s on file %s in line %d',
                    ucfirst($resourceOwner),
                    $userId,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                1
            );
        }

        return $links;
    }

}
