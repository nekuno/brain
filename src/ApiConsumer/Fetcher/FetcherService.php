<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Exception\PaginatedFetchingException;
use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Event\ExceptionEvent;
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
     * @param array $exclude fetcher names that are not to be used
     * @return \ApiConsumer\LinkProcessor\PreprocessedLink[]
     * @throws \Exception
     */
    public function fetch($token, $exclude = array() )
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

        /* @var $links PreprocessedLink[] */
        $links = array();
        try {

            $this->dispatcher->dispatch(\AppEvents::FETCH_START, new FetchEvent($userId, $resourceOwner));

            foreach ($this->options as $fetcher => $fetcherConfig) {

                if (in_array($fetcher, $exclude)){
                    continue;
                }

                if ($fetcherConfig['resourceOwner'] === $resourceOwner) {
                    try {
                        $links = array_merge($links, $this->fetcherFactory->build($fetcher)->fetchLinksFromUserFeed($token, $public));
                    } catch (PaginatedFetchingException $e) {
                        $newLinks = $e->getLinks();
                        $this->logger->warning(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, $fetcher, $resourceOwner, $e->getOriginalException()->getMessage()));
                        $this->dispatcher->dispatch(\AppEvents::EXCEPTION_WARNING, new ExceptionEvent($e, sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, $fetcher, $resourceOwner, $e->getOriginalException()->getMessage())));
                        if (!empty($newLinks)) {
                            $this->logger->info(sprintf('%d links were fetched before the exception happened and are going to be processed.', count($newLinks)));
                            $links = array_merge($links, $newLinks);
                        }

                    } catch (\Exception $e) {
                        $this->logger->error(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, $fetcher, $resourceOwner, $e->getMessage()));
                        continue;
                    }
                }
            }

            foreach($links as $link){
                $link->setToken($token);
                $link->setSource($resourceOwner);
            }

            $this->dispatcher->dispatch(\AppEvents::FETCH_FINISH, new FetchEvent($userId, $resourceOwner));

            $links = $this->processLinks($links, $userId);

        } catch (\Exception $e) {
            throw new \Exception(sprintf('Fetcher: Error fetching from resource "%s" for user "%d". Message: %s on file %s in line %d', $resourceOwner, $userId, $e->getMessage(), $e->getFile(), $e->getLine()), 1);
        }

        return $links;
    }

    /**
     * @param PreprocessedLink[] $links
     * @param int $userId
     * @return array
     */
    public function processLinks(array $links, $userId)
    {
        if (empty($links)){
            return array();
        } else {
            $source = reset($links)->getSource();
        }

        $this->dispatcher->dispatch(\AppEvents::PROCESS_START, new ProcessLinksEvent($userId, $source, $links));

        foreach ($links as $key => $link) {
            try {

                $this->dispatcher->dispatch(\AppEvents::PROCESS_LINK, new ProcessLinkEvent($userId, $source, $link));

                $linkProcessed = $this->linkProcessor->process($link);

                $linkCreated = $this->linkModel->addOrUpdateLink($linkProcessed);

                $linkProcessed['id'] = $linkCreated['id'];
                $this->rateModel->userRateLink($userId, $linkProcessed, RateModel::LIKE, false);

                $links[$key] = $linkProcessed;
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Fetcher: Error processing link "%s" from resource "%s". Reason: %s', $link->getFetched(), $source, $e->getMessage()));
                if ($e instanceof Neo4jException) {
                    $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                }
            }
        }

        $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $source, $links));

        return $links;
    }

}
