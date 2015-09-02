<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\LinkProcessor\LinkProcessor;
use Event\FetchEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use Model\LinkModel;
use Model\User\RateModel;
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
     * @param RateModel $rateModel
     * @param FetcherFactory $fetcherFactory
     * @param EventDispatcher $dispatcher
     * @param array $options
     */
    public function __construct(
        UserProviderInterface $userProvider,
        LinkProcessor $linkProcessor,
        LinkModel $linkModel,
        RateModel $rateModel,
        FetcherFactory $fetcherFactory,
        EventDispatcher $dispatcher,
        array $options
    )
    {

        $this->userProvider = $userProvider;
        $this->linkProcessor = $linkProcessor;
        $this->linkModel = $linkModel;
        $this->rateModel = $rateModel;
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

            //for processing later

            $_SESSION['resourceOwnerToken'] = array(
                'oauthToken' => $user['oauthToken'],
                'createdTime' => time(),
                'expireTime' => $user['expireTime']
            );

            $this->dispatcher->dispatch(\AppEvents::FETCH_START, new FetchEvent($userId, $resourceOwner));

            foreach ($this->options as $fetcher => $fetcherConfig) {

                if ($fetcherConfig['resourceOwner'] === $resourceOwner) {

                    try {
                        $links = array_merge($links, $this->fetcherFactory->build($fetcher)->fetchLinksFromUserFeed($user));
                    } catch (\Exception $e) {
                        $this->logger->error(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, $fetcher, $resourceOwner, $e->getMessage()));
                        continue;
                    }

                }
            }
            $this->dispatcher->dispatch(\AppEvents::FETCH_FINISH, new FetchEvent($userId, $resourceOwner));

            $this->dispatcher->dispatch(\AppEvents::PROCESS_START, new ProcessLinksEvent($userId, $resourceOwner, $links));

            foreach ($links as $key => $link) {
                try {
                    $this->dispatcher->dispatch(\AppEvents::PROCESS_LINK, new ProcessLinkEvent($userId, $resourceOwner, $link));

                    $linkProcessed = $this->linkProcessor->process($link);

                    $linkCreated = $this->linkModel->addLink($linkProcessed);

                    $linkProcessed['id']=$linkCreated['id'];
                    $this->rateModel->userRateLink($userId, $linkProcessed, RateModel::LIKE, false);

                    $links[$key] = $linkProcessed;
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Fetcher: Error processing link "%s" from resource "%s". Reason: %s', $link['url'], $resourceOwner, $e->getMessage()));
                }
            }

            unset($_SESSION['resourceOwnerToken']);

            $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $resourceOwner, $links));

        } catch (\Exception $e) {
            throw new \Exception(sprintf('Fetcher: Error fetching from resource "%s" for user "%d". Message: %s on file %s in line %d', $resourceOwner, $userId, $e->getMessage(), $e->getFile(), $e->getLine()), 1);
        }

        return $links;
    }

}
