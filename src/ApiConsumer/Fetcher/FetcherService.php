<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\LinkProcessor\LinkProcessor;
use Event\FetchingEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use Model\LinkModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class FetcherService
 * @package ApiConsumer\Fetcher
 */
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
     * @var LinkModel
     */
    protected $linkModel;

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

    /**
     * @param UserProviderInterface $userProvider
     * @param LinkProcessor $linkProcessor
     * @param LinkModel $linkModel
     * @param FetcherFactory $fetcherFactory
     * @param EventDispatcher $dispatcher
     * @param array $options
     */
    public function __construct(
        UserProviderInterface $userProvider,
        LinkProcessor $linkProcessor,
        LinkModel $linkModel,
        FetcherFactory $fetcherFactory,
        EventDispatcher $dispatcher,
        array $options
    ) {

        $this->userProvider = $userProvider;
        $this->linkProcessor = $linkProcessor;
        $this->linkModel = $linkModel;
        $this->fetcherFactory = $fetcherFactory;
        $this->dispatcher = $dispatcher;
        $this->options = $options;
    }

    /**
     * @param LoggerInterface $logger
     * @return null|void
     */
    public function setLogger(LoggerInterface $logger)
    {

        $this->logger = $logger;
    }

    /**
     * @param $userId
     * @param $resourceOwner
     * @return array
     * @throws \Exception
     */
    public function fetch($userId, $resourceOwner)
    {

        $links = array();
        try {

            $user = $this->userProvider->getUsersByResource($resourceOwner, $userId);
            if (!$user) {
                throw new \Exception('User not found');
            } else {
                $user = $user[0];
            }

            foreach ($this->options as $fetcher => $fetcherConfig) {

                if ($fetcherConfig['resourceOwner'] === $resourceOwner) {

                    $this->dispatcher->dispatch(\AppEvents::FETCHING_START, new FetchingEvent($userId, $resourceOwner, $fetcher));

                    try {
                        $links = $this->fetcherFactory->build($fetcher)->fetchLinksFromUserFeed($user);
                    } catch (\Exception $e) {
                        $this->logger->error(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, $fetcher, $resourceOwner, $e->getMessage()));
                        continue;
                    }

                    $this->dispatcher->dispatch(\AppEvents::FETCHING_FINISH, new FetchingEvent($userId, $resourceOwner, $fetcher));

                    $this->dispatcher->dispatch(\AppEvents::PROCESS_START, new ProcessLinksEvent($userId, $resourceOwner, $fetcher, $links));

                    foreach ($links as $key => $link) {
                        try {
                            $this->dispatcher->dispatch(\AppEvents::PROCESS_LINK, new ProcessLinkEvent($userId, $resourceOwner, $fetcher, $link));
                            $linkProcessed = $this->linkProcessor->process($link);
                            $linkProcessed['userId'] = $userId;
                            $this->linkModel->addLink($linkProcessed);
                            $links[$key] = $linkProcessed;
                        } catch (\Exception $e) {
                            $this->logger->error(sprintf('Fetcher: Error processing link "%s" from resource "%s". Reason: %s', $link['url'], $resourceOwner, $e->getMessage()));
                        }
                    }

                    $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $resourceOwner, $fetcher, $links));
                }
            }
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    'Fetcher: Error fetching %s for user %d. Message: %s on file %s in line %d',
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
