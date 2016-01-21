<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\LinkProcessor\LinkProcessor;
use Event\FetchEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use Model\LinkModel;
use Model\Neo4j\Neo4jException;
use Model\User\LookUpModel;
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
     * @var LookUpModel
     */
    protected $lookupModel;

    /**
     * @var RateModel
     */
    protected $rateModel;

    /**
     * @param TokensModel $tm
     * @param LinkProcessor $linkProcessor
     * @param LinkModel $linkModel
     * @param RateModel $rateModel
     * @param LookUpModel $lookUpModel
     * @param FetcherFactory $fetcherFactory
     * @param EventDispatcher $dispatcher
     * @param array $options
     */
    public function __construct(
        TokensModel $tm,
        LinkProcessor $linkProcessor,
        LinkModel $linkModel,
        RateModel $rateModel,
        LookUpModel $lookUpModel,
        FetcherFactory $fetcherFactory,
        EventDispatcher $dispatcher,
        array $options
    )
    {

        $this->tm = $tm;
        $this->linkProcessor = $linkProcessor;
        $this->linkModel = $linkModel;
        $this->rateModel = $rateModel;
        $this->lookupModel = $lookUpModel;
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
     * @param array $token
     * @return array
     * @throws \Exception
     */
    public function fetch($token)
    {

        if (!$token) return array();

        if (array_key_exists('id', $token)) {
            $userId = $token['id'];
        } else {
            return array();
        }

        if (array_key_exists('resourceOwner', $token)) {
            $resourceOwner = $token['resourceOwner'];
        } else {
            $resourceOwner = null;
        }

        $public = isset($token['public'])? $token['public'] : false;

        $links = array();
        try {

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

            $links = $this->processLinks($links, $userId, $resourceOwner, $token);

        } catch (\Exception $e) {
            throw new \Exception(sprintf('Fetcher: Error fetching from resource "%s" for user "%d". Message: %s on file %s in line %d', $resourceOwner, $userId, $e->getMessage(), $e->getFile(), $e->getLine()), 1);
        }

        return $links;
    }

    public function processLinks(array $links, $userId, $resourceOwner = null, $token = array())
    {
        $this->dispatcher->dispatch(\AppEvents::PROCESS_START, new ProcessLinksEvent($userId, $resourceOwner, $links));

        foreach ($links as $key => $link) {
            try {

                if ($resourceOwner == TokensModel::FACEBOOK && isset($token['oauthToken']) && isset($token['expireTime'])) {
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
                if ($e instanceof Neo4jException) {
                    $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                }
            }
        }

        $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $resourceOwner, $links));

        return $links;
    }

}
