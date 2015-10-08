<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\LinkProcessor\LinkProcessor;
use Event\FetchEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use Model\LinkModel;
use Model\User\RateModel;
use Model\User\TokensModel;
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
     * @var TokensModel
     */
    protected $tm;

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
     * @param TokensModel $tm
     * @param LinkProcessor $linkProcessor
     * @param LinkModel $linkModel
     * @param RateModel $rateModel
     * @param FetcherFactory $fetcherFactory
     * @param EventDispatcher $dispatcher
     * @param array $options
     */
    public function __construct(
        TokensModel $tm,
        LinkProcessor $linkProcessor,
        LinkModel $linkModel,
        RateModel $rateModel,
        FetcherFactory $fetcherFactory,
        EventDispatcher $dispatcher,
        array $options
    ) {

        $this->tm = $tm;
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
     * @param bool $public
     * @return array
     * @throws \Exception
     */
    public function fetch($userId, $resourceOwner, $public = false)
    {

        $links = array();
        try {
            if (!$public){
                $tokens = $this->tm->getByUserOrResource($userId, $resourceOwner);
                if (!$tokens) {
                    throw new \Exception('User not found');
                } else {
                    $token = current($tokens);
                }
            } else {
                $token = array();
            }


            $this->dispatcher->dispatch(\AppEvents::FETCH_START, new FetchEvent($userId, $resourceOwner));

            foreach ($this->options as $fetcher => $fetcherConfig) {

                if ($fetcherConfig['resourceOwner'] === $resourceOwner) {

                    try {
                        $links = array_merge($links, $this->fetcherFactory->build($fetcher)->fetchLinksFromUserFeed($token, $public));
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

                    if ($resourceOwner == 'facebook') {
                        $link['resourceOwnerToken'] = array(
                            'oauthToken' => $token['oauthToken'],
                            'expireTime' => $token['expireTime']
                        );
                    };

                    $this->dispatcher->dispatch(\AppEvents::PROCESS_LINK, new ProcessLinkEvent($userId, $resourceOwner, $link));

                    $linkProcessed = $this->linkProcessor->process($link);

                    $linkCreated = $this->linkModel->addLink($linkProcessed);

                    $linkProcessed['id'] = $linkCreated['id'];
                    $this->rateModel->userRateLink($userId, $linkProcessed, RateModel::LIKE, false);

                    $links[$key] = $linkProcessed;
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Fetcher: Error processing link "%s" from resource "%s". Reason: %s', $link['url'], $resourceOwner, $e->getMessage()));
                }
            }

            $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $resourceOwner, $links));

        } catch (\Exception $e) {
            throw new \Exception(sprintf('Fetcher: Error fetching from resource "%s" for user "%d". Message: %s on file %s in line %d', $resourceOwner, $userId, $e->getMessage(), $e->getFile(), $e->getLine()), 1);
        }

        return $links;
    }

}
