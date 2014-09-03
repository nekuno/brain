<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Event\LinkEvent;
use ApiConsumer\Event\LinksEvent;
use ApiConsumer\Event\MatchingEvent;
use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\Storage\StorageInterface;
use Event\StatusEvent;
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
     * @var FetcherFactory
     */
    protected $fetcherFactory;

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
        FetcherFactory $fetcherFactory,
        EventDispatcher $dispatcher,
        array $options
    )
    {

        $this->userProvider = $userProvider;
        $this->linkProcessor = $linkProcessor;
        $this->storage = $storage;
        $this->fetcherFactory = $fetcherFactory;
        $this->dispatcher = $dispatcher;
        $this->options = $options;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function fetch($userId, $resourceOwner)
    {

        $links = array();
        try {

            $this->logger->info(sprintf('Fetching links for user %s from resource owner %s', $userId, $resourceOwner));

            $user = $this->userProvider->getUsersByResource($resourceOwner, $userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            foreach ($this->options as $fetcher => $fetcherConfig) {

                if ($fetcherConfig['resourceOwner'] === $resourceOwner) {
                    $user = $this->userProvider->getUsersByResource($resourceOwner, $userId);
                    if (!$user) {
                        throw new \Exception('User not found');
                    }

                    /** @var FetcherInterface $fetcher */
                    $fetcher = new $fetcherConfig['class']($this->resourceOwnerFactory->build($resourceOwner));

                    $event = new StatusEvent($user, $resourceOwner, 'process', true);
                    $this->dispatcher->dispatch(\StatusEvents::USER_DATA_FETCHING_START, $event);

                    $links = $this->fetcherFactory->build($fetcher)->fetchLinksFromUserFeed($user);

                    $event = new StatusEvent($user, $resourceOwner, 'process', true);
                    $this->dispatcher->dispatch(\StatusEvents::USER_DATA_FETCHING_FINISH, $event);

                    $event = new StatusEvent($user, $resourceOwner, 'process', true);
                    $this->dispatcher->dispatch(\StatusEvents::USER_DATA_PROCESS_START, $event);

                    $event = array(
                        'userId' => $userId,
                        'resourceOwner' => $resourceOwner,
                        'fetcher' => $fetcher,
                        'links' => count($links),
                    );
                    $this->dispatcher->dispatch(\AppEvents::PROCESS_LINKS, new LinksEvent($event));

                    foreach ($links as $key => $link) {

                        $links[$key] = $this->linkProcessor->process($link);
                        $event['link'] = $link;
                        $this->dispatcher->dispatch(\AppEvents::PROCESS_LINK, new LinkEvent($event));
                    }

                    $this->storage->storeLinks($user['id'], $links);
                    foreach ($this->storage->getErrors() as $error) {
                        $this->logger->error(sprintf('Error saving link: %s', $error));
                    }

                    $event = new StatusEvent($user, $resourceOwner, 'process', true);
                    $this->dispatcher->dispatch(\StatusEvents::USER_DATA_PROCESS_FINISH, $event);

                    // Dispatch event for enqueue new matching re-calculate task
                    $data = array(
                        'userId' => $user['id'],
                        'service' => $fetcher,
                        'type' => 'process_finished',
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
